USE ssas_db;

-- Student Eligibility IC Number
-- Stores the CSV IC/passport value so UC101 can create eligible accounts
-- only when the administrator runs the eligibility batch.
ALTER TABLE STUDENT_ELIGIBILITY_RECORD
    ADD COLUMN IF NOT EXISTS icNumber VARCHAR(30) NULL AFTER universityEmail;
