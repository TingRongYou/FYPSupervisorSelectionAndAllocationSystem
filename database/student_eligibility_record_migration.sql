CREATE TABLE IF NOT EXISTS STUDENT_ELIGIBILITY_RECORD (
    studentID VARCHAR(20) PRIMARY KEY,
    universityEmail VARCHAR(100) NOT NULL,
    icNumber VARCHAR(30) NULL,
    fullName VARCHAR(100) NOT NULL,
    programme VARCHAR(100) NOT NULL,
    intakeBatch VARCHAR(20) NOT NULL,
    currentSem VARCHAR(10) NOT NULL,
    academicStatus VARCHAR(50) NOT NULL,
    cgpa DECIMAL(5, 4) NOT NULL,
    eligibilityStatus BOOLEAN NOT NULL DEFAULT FALSE,
    importedAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO STUDENT_ELIGIBILITY_RECORD
(
    studentID,
    universityEmail,
    fullName,
    programme,
    intakeBatch,
    currentSem,
    academicStatus,
    cgpa,
    eligibilityStatus
)
SELECT
    SP.studentID,
    U.universityEmail,
    U.fullName,
    SP.programme,
    SP.intakeBatch,
    SP.currentSem,
    SP.academicStatus,
    SP.cgpa,
    SP.eligibilityStatus
FROM STUDENT_PROFILE SP
INNER JOIN USER U
    ON SP.studentID = U.userID
WHERE NOT EXISTS (
    SELECT 1
    FROM STUDENT_ELIGIBILITY_RECORD SER
    WHERE SER.studentID = SP.studentID
);
