ALTER TABLE SUPERVISOR_PROFILE
    ADD COLUMN introVideoStatus VARCHAR(20) NOT NULL DEFAULT 'draft' AFTER introVideoDescription;

UPDATE SUPERVISOR_PROFILE
SET introVideoStatus = 'published'
WHERE introVideoLink IS NOT NULL
AND introVideoLink <> '';
