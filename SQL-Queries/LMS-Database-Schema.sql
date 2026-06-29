-- MySQL Workbench Forward Engineering

SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

-- -----------------------------------------------------
-- Schema mydb
-- -----------------------------------------------------
-- -----------------------------------------------------
-- Schema learningmanagementsystem
-- -----------------------------------------------------

-- -----------------------------------------------------
-- Schema learningmanagementsystem
-- -----------------------------------------------------
CREATE SCHEMA IF NOT EXISTS `learningmanagementsystem` DEFAULT CHARACTER SET utf8mb3 ;
USE `learningmanagementsystem` ;

-- -----------------------------------------------------
-- Table `learningmanagementsystem`.`gradelevel`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `learningmanagementsystem`.`gradelevel` (
  `GradeLevel_ID` INT NOT NULL AUTO_INCREMENT,
  `GradeName` VARCHAR(45) NOT NULL,
  PRIMARY KEY (`GradeLevel_ID`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb3;


-- -----------------------------------------------------
-- Table `learningmanagementsystem`.`section`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `learningmanagementsystem`.`section` (
  `Section_ID` INT NOT NULL AUTO_INCREMENT,
  `SectionName` VARCHAR(45) NOT NULL,
  `FK_GradeLevel_ID` INT NOT NULL,
  PRIMARY KEY (`Section_ID`),
  INDEX `fk_Section_GradeLevel_idx` (`FK_GradeLevel_ID` ASC),
  CONSTRAINT `fk_Section_GradeLevel`
    FOREIGN KEY (`FK_GradeLevel_ID`)
    REFERENCES `learningmanagementsystem`.`gradelevel` (`GradeLevel_ID`)
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb3;


-- -----------------------------------------------------
-- Table `learningmanagementsystem`.`courses`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `learningmanagementsystem`.`courses` (
  `Course_ID` INT NOT NULL AUTO_INCREMENT,
  `CourseCode` VARCHAR(45) NOT NULL,
  `CourseName` VARCHAR(45) NOT NULL,
  `Status` VARCHAR(45) NOT NULL,
  `FK_Section_ID` INT NULL DEFAULT NULL,
  PRIMARY KEY (`Course_ID`),
  INDEX `fk_Courses_Section_idx` (`FK_Section_ID` ASC),
  CONSTRAINT `fk_Courses_Section`
    FOREIGN KEY (`FK_Section_ID`)
    REFERENCES `learningmanagementsystem`.`section` (`Section_ID`)
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb3;


-- -----------------------------------------------------
-- Table `learningmanagementsystem`.`announcements`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `learningmanagementsystem`.`announcements` (
  `Announcement_ID` INT NOT NULL AUTO_INCREMENT,
  `Title` VARCHAR(255) NOT NULL,
  `Message` TEXT NOT NULL,
  `PostDate` DATETIME NOT NULL,
  `FK_Course_ID` INT NOT NULL,
  PRIMARY KEY (`Announcement_ID`),
  INDEX `fk_Announcements_Courses1_idx` (`FK_Course_ID` ASC),
  CONSTRAINT `fk_Announcements_Courses1`
    FOREIGN KEY (`FK_Course_ID`)
    REFERENCES `learningmanagementsystem`.`courses` (`Course_ID`)
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb3;


-- -----------------------------------------------------
-- Table `learningmanagementsystem`.`coursemodule`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `learningmanagementsystem`.`coursemodule` (
  `CourseModule_ID` INT NOT NULL AUTO_INCREMENT,
  `ModuleName` VARCHAR(45) NOT NULL,
  `ModuleSequence` INT NOT NULL,
  `FK_Course_ID` INT NOT NULL,
  PRIMARY KEY (`CourseModule_ID`),
  INDEX `fk_CourseModule_Courses1_idx` (`FK_Course_ID` ASC),
  CONSTRAINT `fk_CourseModule_Courses1`
    FOREIGN KEY (`FK_Course_ID`)
    REFERENCES `learningmanagementsystem`.`courses` (`Course_ID`)
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb3;


-- -----------------------------------------------------
-- Table `learningmanagementsystem`.`assignments`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `learningmanagementsystem`.`assignments` (
  `Assignment_ID` INT NOT NULL AUTO_INCREMENT,
  `Title` VARCHAR(45) NOT NULL,
  `Description` TEXT NOT NULL,
  `DueDate` DATETIME NOT NULL,
  `MaxScore` DECIMAL(5,2) NOT NULL,
  `AttachmentName` VARCHAR(255) NULL DEFAULT NULL,
  `AttachmentPath` VARCHAR(255) NULL DEFAULT NULL,
  `FK_CourseModule_ID` INT NOT NULL,
  PRIMARY KEY (`Assignment_ID`),
  INDEX `fk_Assignments_CourseModule1_idx` (`FK_CourseModule_ID` ASC),
  CONSTRAINT `fk_Assignments_CourseModule1`
    FOREIGN KEY (`FK_CourseModule_ID`)
    REFERENCES `learningmanagementsystem`.`coursemodule` (`CourseModule_ID`)
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb3;


-- -----------------------------------------------------
-- Table `learningmanagementsystem`.`role`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `learningmanagementsystem`.`role` (
  `Role_ID` INT NOT NULL,
  `RoleName` VARCHAR(45) NOT NULL,
  PRIMARY KEY (`Role_ID`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb3;


-- -----------------------------------------------------
-- Table `learningmanagementsystem`.`users`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `learningmanagementsystem`.`users` (
  `User_ID` INT NOT NULL AUTO_INCREMENT,
  `LastName` VARCHAR(45) NOT NULL,
  `FirstName` VARCHAR(45) NOT NULL,
  `Email` VARCHAR(255) NOT NULL,
  `ContactNum` VARCHAR(45) NOT NULL,
  `Gender` VARCHAR(45) NOT NULL,
  `PasswordHash` VARCHAR(100) NOT NULL,
  `DateCreated` DATETIME NOT NULL,
  `Status` VARCHAR(45) NOT NULL,
  `FK_Role_ID` INT NOT NULL,
  `FK_Section_ID` INT NULL DEFAULT NULL,
  PRIMARY KEY (`User_ID`),
  UNIQUE INDEX `Email_UNIQUE` (`Email` ASC),
  INDEX `fk_Users_Role1_idx` (`FK_Role_ID` ASC),
  INDEX `fk_Users_Section_idx` (`FK_Section_ID` ASC),
  CONSTRAINT `fk_Users_Role1`
    FOREIGN KEY (`FK_Role_ID`)
    REFERENCES `learningmanagementsystem`.`role` (`Role_ID`)
    ON DELETE RESTRICT
    ON UPDATE CASCADE,
  CONSTRAINT `fk_Users_Section`
    FOREIGN KEY (`FK_Section_ID`)
    REFERENCES `learningmanagementsystem`.`section` (`Section_ID`)
    ON DELETE SET NULL
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb3;


-- -----------------------------------------------------
-- Table `learningmanagementsystem`.`assignmentsubmission`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `learningmanagementsystem`.`assignmentsubmission` (
  `AssignmentSubmission_ID` INT NOT NULL AUTO_INCREMENT,
  `Filename` VARCHAR(255) NULL DEFAULT NULL,
  `Filepath` VARCHAR(255) NULL DEFAULT NULL,
  `SubmissionText` TEXT NULL DEFAULT NULL,
  `SubmissionDate` DATETIME NOT NULL,
  `Score` DECIMAL(5,2) NULL DEFAULT NULL,
  `Feedback` TEXT NULL DEFAULT NULL,
  `FK_Assignment_ID` INT NOT NULL,
  `FK_User_ID` INT NOT NULL,
  PRIMARY KEY (`AssignmentSubmission_ID`),
  INDEX `fk_AssignmentSubmission_Assignments1_idx` (`FK_Assignment_ID` ASC),
  INDEX `fk_AssignmentSubmission_Users1_idx` (`FK_User_ID` ASC),
  CONSTRAINT `fk_AssignmentSubmission_Assignments1`
    FOREIGN KEY (`FK_Assignment_ID`)
    REFERENCES `learningmanagementsystem`.`assignments` (`Assignment_ID`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_AssignmentSubmission_Users1`
    FOREIGN KEY (`FK_User_ID`)
    REFERENCES `learningmanagementsystem`.`users` (`User_ID`)
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb3;


-- -----------------------------------------------------
-- Table `learningmanagementsystem`.`attendance`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `learningmanagementsystem`.`attendance` (
  `Attendance_ID` INT NOT NULL AUTO_INCREMENT,
  `AttendanceDate` DATE NOT NULL,
  `Status` VARCHAR(45) NOT NULL,
  `FK_Course_ID` INT NOT NULL,
  `FK_Student_ID` INT NOT NULL,
  PRIMARY KEY (`Attendance_ID`),
  INDEX `fk_Attendance_Courses1_idx` (`FK_Course_ID` ASC),
  INDEX `fk_Attendance_Users1_idx` (`FK_Student_ID` ASC),
  CONSTRAINT `fk_Attendance_Courses1`
    FOREIGN KEY (`FK_Course_ID`)
    REFERENCES `learningmanagementsystem`.`courses` (`Course_ID`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_Attendance_Users1`
    FOREIGN KEY (`FK_Student_ID`)
    REFERENCES `learningmanagementsystem`.`users` (`User_ID`)
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb3;


-- -----------------------------------------------------
-- Table `learningmanagementsystem`.`term`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `learningmanagementsystem`.`term` (
  `Term_ID` INT NOT NULL AUTO_INCREMENT,
  `TermName` VARCHAR(100) NOT NULL,
  `StartDate` DATE NOT NULL,
  `EndDate` DATE NOT NULL,
  PRIMARY KEY (`Term_ID`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb3;


-- -----------------------------------------------------
-- Table `learningmanagementsystem`.`enrollment`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `learningmanagementsystem`.`enrollment` (
  `Enrollment_ID` INT NOT NULL AUTO_INCREMENT,
  `EnrollmentDate` DATE NOT NULL,
  `EnrollmentStatus` VARCHAR(45) NOT NULL,
  `FK_Term_ID` INT NULL DEFAULT NULL,
  `FK_User_ID` INT NOT NULL,
  `FK_Course_ID` INT NOT NULL,
  PRIMARY KEY (`Enrollment_ID`),
  INDEX `fk_Enrollment_Users1_idx` (`FK_User_ID` ASC),
  INDEX `fk_Enrollment_Courses1_idx` (`FK_Course_ID` ASC),
  INDEX `fk_Enrollment_Term_idx` (`FK_Term_ID` ASC),
  CONSTRAINT `fk_Enrollment_Courses1`
    FOREIGN KEY (`FK_Course_ID`)
    REFERENCES `learningmanagementsystem`.`courses` (`Course_ID`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_Enrollment_Term`
    FOREIGN KEY (`FK_Term_ID`)
    REFERENCES `learningmanagementsystem`.`term` (`Term_ID`)
    ON DELETE SET NULL
    ON UPDATE CASCADE,
  CONSTRAINT `fk_Enrollment_Users1`
    FOREIGN KEY (`FK_User_ID`)
    REFERENCES `learningmanagementsystem`.`users` (`User_ID`)
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb3;


-- -----------------------------------------------------
-- Table `learningmanagementsystem`.`coursegrade`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `learningmanagementsystem`.`coursegrade` (
  `CourseGrade_id` INT NOT NULL AUTO_INCREMENT,
  `FinalGrade` DECIMAL(5,2) NOT NULL,
  `Remarks` VARCHAR(45) NOT NULL,
  `DateCalculated` DATETIME NOT NULL,
  `FK_Enrollment_ID` INT NOT NULL,
  PRIMARY KEY (`CourseGrade_id`),
  INDEX `fk_CourseGrade_Enrollment1_idx` (`FK_Enrollment_ID` ASC),
  CONSTRAINT `fk_CourseGrade_Enrollment1`
    FOREIGN KEY (`FK_Enrollment_ID`)
    REFERENCES `learningmanagementsystem`.`enrollment` (`Enrollment_ID`)
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb3;


-- -----------------------------------------------------
-- Table `learningmanagementsystem`.`courseinstructors`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `learningmanagementsystem`.`courseinstructors` (
  `CourseInstructors_ID` INT NOT NULL AUTO_INCREMENT,
  `AssignmentDate` DATE NOT NULL,
  `FK_Course_ID` INT NOT NULL,
  `FK_User_ID` INT NOT NULL,
  PRIMARY KEY (`CourseInstructors_ID`),
  INDEX `fk_CourseInstructors_Courses1_idx` (`FK_Course_ID` ASC),
  INDEX `fk_CourseInstructors_Users1_idx` (`FK_User_ID` ASC),
  CONSTRAINT `fk_CourseInstructors_Courses1`
    FOREIGN KEY (`FK_Course_ID`)
    REFERENCES `learningmanagementsystem`.`courses` (`Course_ID`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_CourseInstructors_Users1`
    FOREIGN KEY (`FK_User_ID`)
    REFERENCES `learningmanagementsystem`.`users` (`User_ID`)
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb3;


-- -----------------------------------------------------
-- Table `learningmanagementsystem`.`learningmaterial`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `learningmanagementsystem`.`learningmaterial` (
  `Material_ID` INT NOT NULL AUTO_INCREMENT,
  `MaterialName` VARCHAR(255) NOT NULL,
  `FileName` VARCHAR(45) NOT NULL,
  `FilePath` VARCHAR(255) NOT NULL,
  `FileType` VARCHAR(45) NOT NULL,
  `UploadDate` DATETIME NOT NULL,
  `FK_CourseModule_ID` INT NOT NULL,
  PRIMARY KEY (`Material_ID`),
  INDEX `fk_LearningMaterial_CourseModule1_idx` (`FK_CourseModule_ID` ASC),
  CONSTRAINT `FK_CourseModule`
    FOREIGN KEY (`FK_CourseModule_ID`)
    REFERENCES `learningmanagementsystem`.`coursemodule` (`CourseModule_ID`)
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb3;


-- -----------------------------------------------------
-- Table `learningmanagementsystem`.`sectioncourses`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `learningmanagementsystem`.`sectioncourses` (
  `SectionCourse_ID` INT NOT NULL AUTO_INCREMENT,
  `FK_Section_ID` INT NOT NULL,
  `FK_Course_ID` INT NOT NULL,
  PRIMARY KEY (`SectionCourse_ID`),
  INDEX `fk_SC_Section_idx` (`FK_Section_ID` ASC),
  INDEX `fk_SC_Course_idx` (`FK_Course_ID` ASC),
  CONSTRAINT `fk_SC_Course`
    FOREIGN KEY (`FK_Course_ID`)
    REFERENCES `learningmanagementsystem`.`courses` (`Course_ID`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_SC_Section`
    FOREIGN KEY (`FK_Section_ID`)
    REFERENCES `learningmanagementsystem`.`section` (`Section_ID`)
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb3;


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;


