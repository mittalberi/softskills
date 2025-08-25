-- Roles
INSERT INTO roles (name) VALUES ('student'), ('instructor'), ('admin');

-- Admin user (email: admin@example.com, password: Admin@123)
INSERT INTO users (name,email,password_hash,role_id) VALUES
('Site Admin','admin@example.com','$2b$12$GGc67cBzNWcp/9bkblnaDuTb3tG5i3TXvXksGLqE1VYaiZyaRtoYe', (SELECT id FROM roles WHERE name='admin'));

-- Demo course
INSERT INTO courses (title, short_desc, description, is_published) VALUES
('Quantitative Aptitude','Speed up your Quant basics','<p>Covering Number System, Percentages, Time & Work, Probability, and DI.</p>', 1);

INSERT INTO modules (course_id,title,sort_order) VALUES (1,'Number System Basics',1);
INSERT INTO lessons (module_id,title,content,sort_order) VALUES
(1,'Divisibility Rules','<ul><li>Rule of 2: even last digit</li><li>Rule of 3: sum of digits % 3 == 0</li></ul>',1);

-- Demo questions
INSERT INTO questions (company_tag,topic,question_type,question_text,option_a,option_b,option_c,option_d,correct_answer,explanation,difficulty,created_by) VALUES
('infosys','Percentages','mcq_single','If SP of 10 articles = CP of 15 articles, profit%?','25%','33.33%','50%','66.66%','C','Profit% = (5/10)*100 = 50%', 'easy', 1),
('tcs','Time & Work','mcq_single','A can do a work in 12 days, B in 15 days; together 4 days, remaining by B: days?','6','5','7','8','B','Work in 4 days: 4*(1/12+1/15)= 4*(9/60)=0.6; left 0.4; B needs 0.4/(1/15)=6 days', 'medium', 1),
('wipro','Series','true_false','3,9,27 is GP with ratio 3',NULL,NULL,NULL,NULL,'True','GP definition', 'easy', 1);

-- Demo quiz
INSERT INTO quizzes (course_id,title,duration_minutes,is_mock,is_published,created_by) VALUES
(1,'Aptitude Quick Check',15,0,1,1);

INSERT INTO quiz_questions (quiz_id,question_id,marks,neg_marks,sort_order) VALUES
(1,1,1,0,1),(1,2,1,0,2),(1,3,1,0,3);
