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
TestObject programmeSelect = byXpath(
	'programmeSelect',
	"//form[contains(@class,'allocation-filter-form')]//select[@name='programme']"
)

TestObject applyButton = byXpath(
	'applyButton',
	"//form[contains(@class,'allocation-filter-form')]//button[@type='submit' and normalize-space()='Apply']"
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

TestObject supervisorRosterTitle = byXpath(
	'supervisorRosterTitle',
	"//*[normalize-space()='Supervisor Capacity Roster']"
)

TestObject selectedProgrammeRecord = byXpath(
	'selectedProgrammeRecord',
	"//*[normalize-space()='RSW']"
)

WebUI.openBrowser('')
WebUI.maximizeWindow()

// Login as administrator
WebUI.navigateToUrl(baseUrl + '/client/auth/login.php')
WebUI.setText(emailInput, 'admin@tarc.edu.my')
WebUI.setText(passwordInput, 'admin7181!')
WebUI.click(loginButton)
WebUI.waitForPageLoad(10)

// Go to Allocation Summary report
WebUI.navigateToUrl(baseUrl + '/client/admin/adminAllocationSummary.php')
WebUI.waitForPageLoad(10)

// Verify page loaded
WebUI.verifyTextPresent('Allocation Summary', false)
WebUI.verifyElementPresent(supervisorRosterTitle, 10)

// Apply programme filter
WebUI.selectOptionByLabel(programmeSelect, 'RSW', false)
WebUI.click(applyButton)
WebUI.waitForPageLoad(10)

// Verify report metrics are displayed
WebUI.verifyTextPresent('Allocation Summary', false)
WebUI.verifyElementPresent(slotUtilizationLabel, 10)
WebUI.verifyElementPresent(supervisorsAtCapacityLabel, 10)
WebUI.verifyElementPresent(pendingRequestsLabel, 10)

// Verify roster is displayed and filtered programme appears
WebUI.verifyElementPresent(supervisorRosterTitle, 10)
WebUI.verifyElementPresent(selectedProgrammeRecord, 10)

WebUI.closeBrowser()