import com.kms.katalon.core.webui.keyword.WebUiBuiltInKeywords as WebUI
import com.kms.katalon.core.testobject.TestObject
import com.kms.katalon.core.testobject.ConditionType

TestObject byXpath(String name, String xpath) {
	TestObject obj = new TestObject(name)
	obj.addProperty('xpath', ConditionType.EQUALS, xpath)
	return obj
}

String baseUrl = 'http://localhost/ssas'

TestObject emailInput = byXpath('emailInput', "//input[@name='email']")
TestObject passwordInput = byXpath('passwordInput', "//input[@name='password']")
TestObject loginButton = byXpath('loginButton', "//button[@type='submit' and contains(@class,'btn-login')]")

TestObject programmeSelect = byXpath('programmeSelect', "//select[@name='programme']")
TestObject statusSelect = byXpath('statusSelect', "//select[@name='status']")
TestObject applyButton = byXpath('applyButton', "//form[contains(@class,'filter-form')]//button[@type='submit' and normalize-space()='Apply']")

TestObject pageTitle = byXpath('pageTitle', "//*[normalize-space()='Cohort Overview']")
TestObject filteredStudentsLabel = byXpath('filteredStudentsLabel', "//*[normalize-space()='Filtered Students']")
TestObject allocatedLabel = byXpath('allocatedLabel', "//*[normalize-space()='Allocated']")
TestObject unassignedLabel = byXpath('unassignedLabel', "//*[normalize-space()='Unassigned']")
TestObject allocationProgressLabel = byXpath('allocationProgressLabel', "//*[normalize-space()='Allocation Progress']")
TestObject studentRosterTitle = byXpath('studentRosterTitle', "//*[normalize-space()='Student Roster']")
TestObject assignedStatus = byXpath('assignedStatus', "//span[contains(@class,'status-pill') and normalize-space()='Assigned']")

WebUI.openBrowser('')
WebUI.maximizeWindow()

WebUI.navigateToUrl(baseUrl + '/client/auth/login.php')
WebUI.setText(emailInput, 'admin@tarc.edu.my')
WebUI.setText(passwordInput, 'admin7181!')
WebUI.click(loginButton)
WebUI.waitForPageLoad(10)

WebUI.navigateToUrl(baseUrl + '/client/admin/adminCohortOverview.php')
WebUI.waitForPageLoad(10)

WebUI.verifyElementPresent(pageTitle, 10)

WebUI.selectOptionByLabel(programmeSelect, 'RSW', false)
WebUI.selectOptionByLabel(statusSelect, 'Assigned', false)
WebUI.click(applyButton)
WebUI.waitForPageLoad(10)

WebUI.verifyElementPresent(pageTitle, 10)
WebUI.verifyElementPresent(filteredStudentsLabel, 10)
WebUI.verifyElementPresent(allocatedLabel, 10)
WebUI.verifyElementPresent(unassignedLabel, 10)
WebUI.verifyElementPresent(allocationProgressLabel, 10)
WebUI.verifyElementPresent(studentRosterTitle, 10)
WebUI.verifyTextPresent('RSW', false)
WebUI.verifyElementPresent(assignedStatus, 10)

WebUI.closeBrowser()