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

TestObject pageTitle = byXpath('pageTitle', "//*[normalize-space()='My Supervisees']")
TestObject subtitle = byXpath('subtitle', "//*[contains(normalize-space(),'Students under your supervision')]")

TestObject summaryStrip = byXpath('summaryStrip', "//*[contains(@class,'summary-strip')]")
TestObject totalSuperviseesSummary = byXpath('totalSuperviseesSummary', "//*[contains(@class,'summary-label') and contains(normalize-space(),'Total') and contains(normalize-space(),'Supervisees')]")
TestObject activeStudentsSummary = byXpath('activeStudentsSummary', "//*[contains(@class,'summary-label') and contains(normalize-space(),'Active') and contains(normalize-space(),'Students')]")

TestObject tableHeaderStudentName = byXpath('tableHeaderStudentName', "//th[normalize-space()='Student Name']")
TestObject tableHeaderStudentID = byXpath('tableHeaderStudentID', "//th[normalize-space()='Student ID']")
TestObject tableHeaderResearchTitle = byXpath('tableHeaderResearchTitle', "//th[normalize-space()='Research Title']")
TestObject tableHeaderProgramme = byXpath('tableHeaderProgramme', "//th[normalize-space()='Programme']")
TestObject tableHeaderStatus = byXpath('tableHeaderStatus', "//th[normalize-space()='Status']")
TestObject tableHeaderActions = byXpath('tableHeaderActions', "//th[normalize-space()='Actions']")

TestObject firstRow = byXpath('firstRow', "//table/tbody/tr[1]")
TestObject activeStatus = byXpath('activeStatus', "(//span[contains(@class,'status') and normalize-space()='Active'])[1]")
TestObject showingText = byXpath('showingText', "//*[contains(normalize-space(),'Showing') and contains(normalize-space(),'supervisees')]")
TestObject paginationText = byXpath('paginationText', "//*[contains(normalize-space(),'Page') and contains(normalize-space(),'of')]")

WebUI.openBrowser('')
WebUI.maximizeWindow()

WebUI.navigateToUrl(baseUrl + '/client/auth/login.php')
WebUI.setText(emailInput, supervisorEmail)
WebUI.setText(passwordInput, supervisorPassword)
WebUI.click(loginButton)
WebUI.waitForPageLoad(10)

WebUI.navigateToUrl(baseUrl + '/client/supervisor/supervisorMySupervisees.php?page=1')
WebUI.waitForPageLoad(10)

WebUI.verifyElementPresent(pageTitle, 10)
WebUI.verifyElementPresent(subtitle, 10)

WebUI.verifyElementPresent(summaryStrip, 10)
WebUI.verifyElementPresent(totalSuperviseesSummary, 10)
WebUI.verifyElementPresent(activeStudentsSummary, 10)

WebUI.verifyElementPresent(tableHeaderStudentName, 10)
WebUI.verifyElementPresent(tableHeaderStudentID, 10)
WebUI.verifyElementPresent(tableHeaderResearchTitle, 10)
WebUI.verifyElementPresent(tableHeaderProgramme, 10)
WebUI.verifyElementPresent(tableHeaderStatus, 10)
WebUI.verifyElementPresent(tableHeaderActions, 10)

WebUI.verifyElementPresent(firstRow, 10)
WebUI.verifyElementPresent(activeStatus, 10)
WebUI.verifyElementPresent(showingText, 10)
WebUI.verifyElementPresent(paginationText, 10)

WebUI.closeBrowser()