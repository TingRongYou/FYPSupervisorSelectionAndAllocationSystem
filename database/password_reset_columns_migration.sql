USE ssas_db;

-- Password Reset Columns
-- Adds token storage used by forgotPassword.php and changePassword.php.
ALTER TABLE USER
    ADD COLUMN IF NOT EXISTS resetToken VARCHAR(64) NULL AFTER profilePhotoPath,
    ADD COLUMN IF NOT EXISTS resetExpires DATETIME NULL AFTER resetToken;
