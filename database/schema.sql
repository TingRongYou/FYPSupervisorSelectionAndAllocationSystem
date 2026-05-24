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
    password VARCHAR(255) NOT NULL
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
    employmentCategory VARCHAR(50) NOT NULL,
    introVideoLink VARCHAR(255) NULL,
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

    FOREIGN KEY (supervisorID) REFERENCES SUPERVISOR_PROFILE(supervisorID) ON DELETE CASCADE ON UPDATE CASCADE
);
