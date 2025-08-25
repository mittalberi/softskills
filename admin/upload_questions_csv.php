<?php
require_once '../includes/db.php';
require_once '../includes/auth_guard.php';
require_once '../includes/csrf.php';
require_role('instructor');

/**
 * Serve a sample CSV when requested: /admin/upload_questions_csv.php?sample=1
 */
if (isset($_GET['sample'])) {
  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename="questions_sample.csv"');
  $out = fopen('php://output', 'w');
  fputcsv($out, ['company_tag','topic','question_type','question_text','option_a','option_b','option_c','option_d','correct_answer','explanation','difficulty']);
  fputcsv($out, ['tcs','Time & Work','mcq_single',"A and B can finish a work in 12 and 15 days respectively. If they work together for 4 days, how many more days will B alone take to finish the remaining work?",'—','—','—','—','B',"Work in 4 days: 4*(1/12+1/15)=0.6; remaining 0.4; B needs 0.4/(1/15)=6 days",'medium']);
  fputcsv($out, ['infosys','Probability','true_false',"If P(A)=1, then A is a sure event",null,null,null,null,'True',"Definition of sure event",'easy']);
  fputcsv($out, ['wipro','Vocabulary','short',"Plural of 'criterion'?",null,null,null,null,'criteria',"",'easy']);
  fputcsv($out, ['generic','Percentages','mcq_multi',"Select all that are correct representations of 10%:",'1/10','0.10','10/100','0.001','A|B|C',"10% = 10/100 = 0.10 = 1/10",'easy']);
  fclose($out);
  exit;
}

$expected = ['company_tag','topic','question_type','question_text','option_a','option_b','option_c','option_d','correct_answer','explanation','difficulty'];
$allowedCompany = ['infosys','tcs','wipro','generic'];
$allowedQType   = ['mcq_single','mcq_multi','true_false','short'];
$allowedDiff    = ['easy','medium','hard'];

$report = null; $msg = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv'])) {
  csrf_verify();

  // Basic file checks
  if (!is_uploaded_file($_FILES['csv']['tmp_name'])) {
    $msg = 'Upload failed. No file received.';
  } else {
    $tmp   = $_FILES['csv']['tmp_name'];
    $name  = $_FILES['csv']['name'];
    $size  = (int)($_FILES['csv']['size'] ?? 0);
    $type  = (string)($_FILES['csv']['type'] ?? '');

    if ($size <= 0) {
      $msg = 'Empty file.';
    } elseif ($size > 5 * 1024 * 1024) { // 5 MB
      $msg = 'File too large. Max 5 MB.';
    } else {
      // Open and parse
      $f = fopen($tmp, 'r');
      if (!$f) {
        $msg = 'Unable to open uploaded file.';
      } else {
        // Read header; handle UTF‑8 BOM and trim/lower
        $rawHdr = fgetcsv($f);
        if ($rawHdr === false) {
          $msg = 'CSV appears to be empty.';
        } else {
          // Remove BOM if present on first header cell
          if (isset($rawHdr[0])) {
            $rawHdr[0] = preg_replace('/^\xEF\xBB\xBF/', '', $rawHdr[0]);
          }
          $norm = array_map(function($h){
            return strtolower(trim($h ?? ''));
          }, $rawHdr);

          if ($norm !== $expected) {
            $msg = 'Invalid CSV header. Expect exactly: ' . implode(',', $expected);
          } else {
            // Prepare insert
            $ins = $pdo->prepare('INSERT INTO questions
              (company_tag,topic,question_type,question_text,option_a,option_b,option_c,option_d,correct_answer,explanation,difficulty,created_by)
              VALUES (?,?,?,?,?,?,?,?,?,?,?,?)');

            $inserted = 0; $skipped = 0; $errors = [];
            $line = 1; // header line
            $pdo->beginTransaction();
            try {
              while (($row = fgetcsv($f)) !== false) {
                $line++;

                // Skip fully-empty lines
                if (count(array_filter($row, fn($v) => trim((string)$v) !== '')) === 0) {
                  continue;
                }

                // Normalize row cells (trim)
                // Ensure at least 11 columns exist
                for ($i=0; $i<count($expected); $i++) {
                  if (!array_key_exists($i, $row)) $row[$i] = null;
                }
                [$company,$topic,$type,$qtext,$a,$b,$c,$d,$correct,$expl,$diff] = array_map(fn($v)=> is_null($v)? null : trim((string)$v), $row);

                // Basic validations
                $errs = [];

                // company_tag
                $company_l = strtolower($company ?? '');
                if (!in_array($company_l, $allowedCompany, true)) {
                  $errs[] = "Invalid company_tag '$company'. Allowed: " . implode('/', $allowedCompany);
                } else {
                  $company = $company_l;
                }

                // question_type
                $type_l = strtolower($type ?? '');
                if (!in_array($type_l, $allowedQType, true)) {
                  $errs[] = "Invalid question_type '$type'. Allowed: " . implode('/', $allowedQType);
                } else {
                  $type = $type_l;
                }

                // difficulty
                $diff_l = strtolower($diff ?? '');
                if (!in_array($diff_l, $allowedDiff, true)) {
                  $errs[] = "Invalid difficulty '$diff'. Allowed: " . implode('/', $allowedDiff);
                } else {
                  $diff = $diff_l;
                }

                // question_text required
                if ($qtext === null || $qtext === '') {
                  $errs[] = 'question_text is required';
                }

                // Validate options & correct_answer by type
                if ($type === 'mcq_single') {
                  // Need at least one option; correct must be A/B/C/D and that option must exist
                  $opts = ['A'=>$a,'B'=>$b,'C'=>$c,'D'=>$d];
                  $hasAny = array_reduce($opts, fn($c,$v)=>$c || (trim((string)$v) !== ''), false);
                  if (!$hasAny) $errs[] = 'At least one option (A–D) is required for mcq_single.';
                  $corr = strtoupper($correct ?? '');
                  if (!in_array($corr, ['A','B','C','D'], true)) {
                    $errs[] = "correct_answer must be one of A/B/C/D for mcq_single.";
                  } elseif (trim((string)$opts[$corr]) === '') {
                    $errs[] = "correct_answer '$corr' specified but option $corr is empty.";
                  } else {
                    $correct = $corr;
                  }
                } elseif ($type === 'mcq_multi') {
                  // correct like A|C, each present and option non-empty
                  $corr = strtoupper($correct ?? '');
                  if (!preg_match('/^[A-D](\|[A-D])*$/', $corr)) {
                    $errs[] = "correct_answer must be like 'A|C' for mcq_multi.";
                  } else {
                    $parts = explode('|', $corr);
                    $opts = ['A'=>$a,'B'=>$b,'C'=>$c,'D'=>$d];
                    foreach ($parts as $p) {
                      if (trim((string)$opts[$p]) === '') {
                        $errs[] = "correct_answer includes '$p' but option $p is empty.";
                        break;
                      }
                    }
                    $correct = $corr;
                  }
                } elseif ($type === 'true_false') {
                  $corr = ucfirst(strtolower($correct ?? ''));
                  if (!in_array($corr, ['True','False'], true)) {
                    $errs[] = "correct_answer must be 'True' or 'False' for true_false.";
                  } else {
                    $correct = $corr;
                  }
                } elseif ($type === 'short') {
                  if ($correct === null || $correct === '') {
                    $errs[] = "correct_answer is required for short (case-insensitive match).";
                  }
                }

                if ($errs) {
                  $skipped++;
                  $errors[] = "Line $line: " . implode('; ', $errs);
                  continue;
                }

                // Insert row
                $ins->execute([
                  $company,$topic,$type,$qtext,$a,$b,$c,$d,$correct,$expl,$diff,$_SESSION['user']['id']
                ]);
                $inserted++;
              }
              $pdo->commit();
              $report = [
                'inserted' => $inserted,
                'skipped'  => $skipped,
                'errors'   => $errors,
              ];
              $msg = "Import finished. Inserted: $inserted; Skipped: $skipped.";
            } catch (Throwable $e) {
              $pdo->rollBack();
              $msg = 'Import failed: ' . $e->getMessage();
            }
            fclose($f);
          }
        }
      }
    }
  }
}

include '../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center">
  <h4 class="mb-0">Upload Questions (CSV)</h4>
  <div class="d-flex gap-2">
    <a class="btn btn-outline-secondary btn-sm" href="<?= url('samples/questions_sample.csv') ?>">Download Sample (static)</a>
    <a class="btn btn-outline-primary btn-sm" href="<?= url('admin/upload_questions_csv.php?sample=1') ?>">Download Sample (generated)</a>
  </div>
</div>

<?php if (!empty($msg)): ?>
  <div class="alert alert-info mt-3"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<?php if (!empty($report)): ?>
  <div class="card my-3">
    <div class="card-body">
      <h6 class="card-title mb-2">Import Report</h6>
      <ul class="mb-2">
        <li><strong>Inserted:</strong> <?= (int)$report['inserted'] ?></li>
        <li><strong>Skipped:</strong> <?= (int)$report['skipped'] ?></li>
      </ul>
      <?php if (!empty($report['errors'])): ?>
        <details>
          <summary>Show errors (<?= count($report['errors']) ?>)</summary>
          <ul class="mt-2">
            <?php foreach ($report['errors'] as $e): ?>
              <li class="text-danger small"><?= htmlspecialchars($e) ?></li>
            <?php endforeach; ?>
          </ul>
        </details>
      <?php endif; ?>
    </div>
  </div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" class="col-lg-8 mt-3">
  <?php csrf_field(); ?>
  <div class="mb-3">
    <label class="form-label">CSV file</label>
    <input type="file" name="csv" class="form-control" accept=".csv" required>
    <div class="form-text">
      Header must be exactly:<br>
      <code><?= implode(',', $expected) ?></code><br>
      Max size: 5&nbsp;MB. Encoding: UTF‑8. For <code>mcq_multi</code> use answers like <code>A|C</code>.
    </div>
  </div>
  <button class="btn btn-primary">Upload</button>
</form>

<?php include '../includes/footer.php'; ?>