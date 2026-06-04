-- Initialise database
DROP DATABASE IF EXISTS ssas_db;
CREATE DATABASE ssas_db;
USE ssas_db;

-- Create user table
CREATE TABLE USER (
    userID VARCHAR(20) PRIMARY KEY,
    fullName VARCHAR(100) NOT NULL,
    universityEmail VARCHAR(100) UNIQUE NOT NULL,
    systemRole VARCHAR(50) NOT NULL,
    activeStatus BOOLEAN NOT NULL DEFAULT TRUE,
    profilePhotoPath VARCHAR(255) NULL,
    resetToken VARCHAR(64) NULL,
    resetExpires DATETIME NULL,
    password VARCHAR(255) NOT NULL
);

CREATE TABLE USER_ACTIVITY_STATUS (
    userID VARCHAR(20) PRIMARY KEY,
    systemRole VARCHAR(50) NOT NULL,
    lastSeenAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    isOnline BOOLEAN NOT NULL DEFAULT FALSE,

    FOREIGN KEY (userID) REFERENCES USER(userID) ON DELETE CASCADE ON UPDATE CASCADE
);

-- Create student profile table
CREATE TABLE STUDENT_PROFILE (
    studentID VARCHAR(20) PRIMARY KEY,
    programme VARCHAR(100) NOT NULL,
    intakeBatch VARCHAR(20) NOT NULL,
    currentSem VARCHAR(10) NOT NULL,
    academicStatus VARCHAR(50) NOT NULL,
    cgpa DECIMAL(5, 4) NOT NULL,
    contactNumber VARCHAR(20) NULL,
    personalBio VARCHAR(500) NULL,
    avatarFilePath VARCHAR(255) NULL,
    linkedInURL VARCHAR(255) NULL,
    githubURL VARCHAR(255) NULL,
    portfolioURL VARCHAR(255) NULL,
    eligibilityStatus BOOLEAN NOT NULL DEFAULT FALSE,

    -- If userID is deleted from USER table, delete the student profile as well
    FOREIGN KEY (studentID) REFERENCES USER(userID) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE STUDENT_ELIGIBILITY_RECORD (
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

CREATE TABLE ELIGIBILITY_RULE_CONFIGURATION (
    ruleID INT PRIMARY KEY,
    minimumCGPA DECIMAL(3, 2) NOT NULL DEFAULT 2.00,
    requiredNextSemester VARCHAR(10) NOT NULL DEFAULT 'Y2S3',
    blockedAcademicStatus VARCHAR(50) NOT NULL DEFAULT 'EF',
    updatedAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO ELIGIBILITY_RULE_CONFIGURATION
(
    ruleID,
    minimumCGPA,
    requiredNextSemester,
    blockedAcademicStatus
)
VALUES
(
    1,
    2.00,
    'Y2S3',
    'EF'
);

-- Create quota configuration table
CREATE TABLE QUOTA_CONFIGURATION (
    quotaID INT PRIMARY KEY AUTO_INCREMENT,
    quotaTierName VARCHAR(50) NOT NULL,
    maxSuperviseesAllowed INT NOT NULL
);

-- Create supervisor profile table
CREATE TABLE SUPERVISOR_PROFILE (
    supervisorID VARCHAR(20) PRIMARY KEY,
    quotaID INT NOT NULL,
    assignedQuotaLimit INT NULL,
    employmentCategory VARCHAR(50) NOT NULL,
    activeTime VARCHAR(100) NULL,
    introVideoLink VARCHAR(255) NULL,
    introVideoDescription VARCHAR(500) NULL,
    introVideoStatus VARCHAR(20) NOT NULL DEFAULT 'draft',
    supervisorBio VARCHAR(500) NULL,
    programme VARCHAR(100) NOT NULL,

    FOREIGN KEY (supervisorID) REFERENCES USER(userID) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (quotaID) REFERENCES QUOTA_CONFIGURATION(quotaID) ON DELETE CASCADE ON UPDATE CASCADE
);

-- Create research tag table
CREATE TABLE RESEARCH_TAG (
    tagID INT PRIMARY KEY AUTO_INCREMENT,
    tagName VARCHAR(50) UNIQUE NOT NULL
);

-- Create student tag selection table
CREATE TABLE STUDENT_TAG_SELECTION (
    studentID VARCHAR(20) NOT NULL,
    tagID INT NOT NULL,

    -- Create composite primary key (if create separately, will cause error "Multiple primary key defined")
    PRIMARY KEY (studentID, tagID),

    FOREIGN KEY (studentID) REFERENCES STUDENT_PROFILE(studentID) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (tagID) REFERENCES RESEARCH_TAG(tagID) ON DELETE CASCADE ON UPDATE CASCADE
);

-- Create supervisor tag selection table
CREATE TABLE SUPERVISOR_TAG_SELECTION (
    supervisorID VARCHAR(20) NOT NULL,
    tagID INT NOT NULL,

    -- Create composite primary key
    PRIMARY KEY (supervisorID, tagID),

    FOREIGN KEY (supervisorID) REFERENCES SUPERVISOR_PROFILE(supervisorID) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (tagID) REFERENCES RESEARCH_TAG(tagID) ON DELETE CASCADE ON UPDATE CASCADE
);

-- Create system phase timeline table
CREATE TABLE SYSTEM_PHASE_TIMELINE (
    phaseID INT PRIMARY KEY,
    phaseName VARCHAR(50) NOT NULL,
    startTimestamp DATETIME NOT NULL,
    endTimestamp DATETIME NOT NULL
);

CREATE TABLE ALLOCATION_WINDOW_CONFIG (
    configID INT PRIMARY KEY,
    initialAllocationDate DATETIME NOT NULL,
    finalAllocationDate DATETIME NOT NULL,
    updatedAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP
);

-- Create application request table
CREATE TABLE APPLICATION_REQUEST (
    requestID INT PRIMARY KEY AUTO_INCREMENT,
    studentID VARCHAR(20) NOT NULL,
    supervisorID VARCHAR(20) NOT NULL,
    projectTitle VARCHAR(255) NOT NULL,
    proposalPDFPath VARCHAR(255) NOT NULL,
    applicationDate DATETIME NOT NULL,
    ttlExpirationTimestamp DATETIME NOT NULL,
    decisionStatus VARCHAR(50) NOT NULL,
    supervisorComment TEXT NULL,

    FOREIGN KEY (studentID) REFERENCES STUDENT_PROFILE(studentID) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (supervisorID) REFERENCES SUPERVISOR_PROFILE(supervisorID) ON DELETE CASCADE ON UPDATE CASCADE
);

-- Create allocation record table
CREATE TABLE ALLOCATION_RECORD (
    allocationID INT PRIMARY KEY AUTO_INCREMENT,
    studentID VARCHAR(20) UNIQUE NOT NULL,
    supervisorID VARCHAR(20) NOT NULL,
    allocationDate DATETIME NOT NULL,
    allocationMethod VARCHAR(50) NOT NULL,

    FOREIGN KEY (studentID) REFERENCES STUDENT_PROFILE(studentID) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (supervisorID) REFERENCES SUPERVISOR_PROFILE(supervisorID) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE AUTO_ALLOCATION_LOG (
    logID INT PRIMARY KEY AUTO_INCREMENT,
    triggeredByAdminID VARCHAR(20) NULL,
    triggeredAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    finalAllocationDate DATETIME NULL,
    eligibleCount INT NOT NULL DEFAULT 0,
    matchedCount INT NOT NULL DEFAULT 0,
    unassignedCount INT NOT NULL DEFAULT 0,
    logStatus VARCHAR(30) NOT NULL,
    resultMessage VARCHAR(500) NOT NULL,

    FOREIGN KEY (triggeredByAdminID) REFERENCES USER(userID) ON DELETE SET NULL ON UPDATE CASCADE
);

-- Create supervisor review table
CREATE TABLE SUPERVISOR_REVIEW (
    reviewID INT PRIMARY KEY AUTO_INCREMENT,
    allocationID INT UNIQUE NOT NULL,
    trueStudentID VARCHAR(20) NOT NULL,
    starRating INT NOT NULL CHECK (starRating >= 1 and starRating <= 5),
    textFeedback VARCHAR(1000) NULL,
    isAnonymous BOOLEAN NOT NULL DEFAULT FALSE,

    FOREIGN KEY (allocationID) REFERENCES ALLOCATION_RECORD(allocationID) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (trueStudentID) REFERENCES STUDENT_PROFILE(studentID) ON DELETE CASCADE ON UPDATE CASCADE
);

-- Create past project table
CREATE TABLE PAST_PROJECT (
    projectID INT PRIMARY KEY AUTO_INCREMENT,
    supervisorID VARCHAR(20) NOT NULL,
    projectTitle VARCHAR(255) NOT NULL,
    completionYear INT NOT NULL,
    alumniName VARCHAR(100) NOT NULL,
    projectDescription TEXT NULL,
    projectPDFPath VARCHAR(255) NULL,
    projectImagePath VARCHAR(255) NULL,

    FOREIGN KEY (supervisorID) REFERENCES SUPERVISOR_PROFILE(supervisorID) ON DELETE CASCADE ON UPDATE CASCADE
);
