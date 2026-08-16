import com.kms.katalon.core.webui.keyword.WebUiBuiltInKeywords as WebUI
import com.kms.katalon.core.testobject.TestObject
import com.kms.katalon.core.testobject.ConditionType

TestObject byXpath(String name, String xpath) {
	TestObject obj = new TestObject(name)
	obj.addProperty('xpath', ConditionType.EQUALS, xpath)
	return obj
}

String baseUrl = 'http://localhost/ssas'
String supervisorEmail = 'leezq1129@tarc.edu.my'
String supervisorPassword = 'leezq1129!'

TestObject emailInput = byXpath('emailInput', "//input[@name='email']")
TestObject passwordInput = byXpath('passwordInput', "//input[@name='password']")
TestObject loginButton = byXpath('loginButton', "//button[@type='submit' and contains(@class,'btn-login')]")

TestObject pageTitle = byXpath('pageTitle', "//*[normalize-space()='Supervision History']")
TestObject totalCareerSupervisions = byXpath('totalCareerSupervisions', "//*[normalize-space()='Total Career Supervisions']")
TestObject primaryField = byXpath('primaryField', "//*[normalize-space()='Primary Field']")
TestObject assignmentLog = byXpath('assignmentLog', "//*[normalize-space()='Assignment Log']")

TestObject yearHeader = byXpath('yearHeader', "//th[normalize-space()='Year']")
TestObject semesterHeader = byXpath('semesterHeader', "//th[normalize-space()='Semester']")
TestObject studentNameHeader = byXpath('studentNameHeader', "//th[normalize-space()='Student Name']")
TestObject projectTitleHeader = byXpath('projectTitleHeader', "//th[normalize-space()='Project Title']")
TestObject statusHeader = byXpath('statusHeader', "//th[normalize-space()='Status']")

TestObject firstHistoryRow = byXpath('firstHistoryRow', "//table[contains(@class,'report-table')]/tbody/tr[1]")
TestObject showingText = byXpath('showingText', "//*[contains(normalize-space(),'Showing') and contains(normalize-space(),'historical record')]")
TestObject paginationText = byXpath('paginationText', "//*[contains(normalize-space(),'Page') and contains(normalize-space(),'of')]")

WebUI.openBrowser('')
WebUI.maximizeWindow()

WebUI.navigateToUrl(baseUrl + '/client/auth/login.php')
WebUI.setText(emailInput, supervisorEmail)
WebUI.setText(passwordInput, supervisorPassword)
WebUI.click(loginButton)
WebUI.waitForPageLoad(10)

WebUI.navigateToUrl(baseUrl + '/client/supervisor/supervisorHistoryLog.php')
WebUI.waitForPageLoad(10)

WebUI.verifyElementPresent(pageTitle, 10)
WebUI.verifyElementPresent(totalCareerSupervisions, 10)
WebUI.verifyElementPresent(primaryField, 10)
WebUI.verifyElementPresent(assignmentLog, 10)

WebUI.verifyElementPresent(yearHeader, 10)
WebUI.verifyElementPresent(semesterHeader, 10)
WebUI.verifyElementPresent(studentNameHeader, 10)
WebUI.verifyElementPresent(projectTitleHeader, 10)
WebUI.verifyElementPresent(statusHeader, 10)

WebUI.verifyElementPresent(firstHistoryRow, 10)
WebUI.verifyElementPresent(showingText, 10)
WebUI.verifyElementPresent(paginationText, 10)

WebUI.closeBrowser()