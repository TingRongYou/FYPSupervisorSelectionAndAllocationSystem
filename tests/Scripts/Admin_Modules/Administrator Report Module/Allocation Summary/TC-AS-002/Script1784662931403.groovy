import com.kms.katalon.core.webui.keyword.WebUiBuiltInKeywords as WebUI
import com.kms.katalon.core.testobject.TestObject
import com.kms.katalon.core.testobject.ConditionType

TestObject byXpath(String name, String xpath) {
	TestObject obj = new TestObject(name)
	obj.addProperty('xpath', ConditionType.EQUALS, xpath)
	return obj
}

String baseUrl = 'http://localhost/ssas'

// Login objects
TestObject emailInput = byXpath('emailInput', "//input[@name='email']")
TestObject passwordInput = byXpath('passwordInput', "//input[@name='password']")
TestObject loginButton = byXpath('loginButton', "//button[@type='submit' and contains(@class,'btn-login')]")

// Allocation Summary objects
TestObject pageTitle = byXpath(
	'pageTitle',
	"//*[normalize-space()='Allocation Summary']"
)

TestObject slotUtilizationLabel = byXpath(
	'slotUtilizationLabel',
	"//*[normalize-space()='Slot Utilization']"
)

TestObject supervisorsAtCapacityLabel = byXpath(
	'supervisorsAtCapacityLabel',
	"//*[normalize-space()='Supervisors at Capacity']"
)

TestObject pendingRequestsLabel = byXpath(
	'pendingRequestsLabel',
	"//*[normalize-space()='Pending Requests']"
)

TestObject noRecordMessage = byXpath(
	'noRecordMessage',
	"//*[contains(normalize-space(),'No supervisor data available')]"
)

WebUI.openBrowser('')
WebUI.maximizeWindow()

// Login as administrator
WebUI.navigateToUrl(baseUrl + '/client/auth/login.php')
WebUI.setText(emailInput, 'admin@tarc.edu.my')
WebUI.setText(passwordInput, 'admin7181!')
WebUI.click(loginButton)
WebUI.waitForPageLoad(10)

// Open Allocation Summary with a programme that has no supervisor records
WebUI.navigateToUrl(baseUrl + '/client/admin/adminAllocationSummary.php?programme=NO_RECORD_TEST&rosterPage=1')
WebUI.waitForPageLoad(10)

// Verify page loaded
WebUI.verifyElementPresent(pageTitle, 10)

// Verify summary metric labels are displayed
WebUI.verifyElementPresent(slotUtilizationLabel, 10)
WebUI.verifyElementPresent(supervisorsAtCapacityLabel, 10)
WebUI.verifyElementPresent(pendingRequestsLabel, 10)

// Verify no-record result
WebUI.verifyElementPresent(noRecordMessage, 10)

WebUI.closeBrowser()