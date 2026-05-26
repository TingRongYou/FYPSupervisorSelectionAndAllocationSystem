ALTER TABLE SUPERVISOR_PROFILE
    ADD COLUMN activeTime VARCHAR(100) NULL AFTER employmentCategory;

UPDATE SUPERVISOR_PROFILE
SET activeTime = 'Consultation by appointment'
WHERE activeTime IS NULL OR activeTime = '';
