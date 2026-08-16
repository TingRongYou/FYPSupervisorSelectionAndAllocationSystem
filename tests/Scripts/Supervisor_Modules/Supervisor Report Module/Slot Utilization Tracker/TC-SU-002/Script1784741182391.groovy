import com.kms.katalon.core.webui.keyword.WebUiBuiltInKeywords as WebUI
import com.kms.katalon.core.testobject.TestObject
import com.kms.katalon.core.testobject.ConditionType
import com.kms.katalon.core.util.KeywordUtil

TestObject byXpath(String name, String xpath) {
	TestObject obj = new TestObject(name)
	obj.addProperty('xpath', ConditionType.EQUALS, xpath)
	return obj
}

String runMysql(String sqlText) {
	List<String> command = [
		'C:\\xampp\\mysql\\bin\\mysql.exe',
		'-u', 'root',
		'-D', 'ssas_db',
		'-N',
		'-B',
		'-e', sqlText
	]

	Process process = new ProcessBuilder(command).redirectErrorStream(true).start()
	String output = process.inputStream.text.trim()
	int exitCode = process.waitFor()

	if (exitCode != 0) {
		KeywordUtil.markFailed("MySQL command failed: " + output)
	}

	return output
}

String baseUrl = 'http://localhost/ssas'

String supervisorEmail = 'leezq1129@tarc.edu.my'
String supervisorPassword = 'leezq1129!'
String supervisorID = '1129'

Integer originalQuota = null
boolean browserOpened = false

TestObject emailInput = byXpath('emailInput', "//input[@name='email']")
TestObject passwordInput = byXpath('passwordInput', "//input[@name='password']")
TestObject loginButton = byXpath('loginButton', "//button[@type='submit' and contains(@class,'btn-login')]")

TestObject pageTitle = byXpath('pageTitle', "//*[normalize-space()='Slot Utilization']")
TestObject liveUtilization = byXpath('liveUtilization', "//*[normalize-space()='Live Utilization']")
TestObject slotsFilled = byXpath('slotsFilled', "//*[contains(normalize-space(),'Slots Filled')]")
TestObject quotaFilled = byXpath('quotaFilled', "//*[normalize-space()='Quota Filled']")
TestObject quotaFullyFilledMessage = byXpath('quotaFullyFilledMessage', "//*[contains(normalize-space(),'Quota is fully filled')]")
TestObject allocationHealth = byXpath('allocationHealth', "//*[normalize-space()='Allocation Health']")
TestObject availableCapacity = byXpath('availableCapacity', "//*[normalize-space()='Available Capacity']")

try {
	String originalQuotaText = runMysql(
		"SELECT assignedQuotaLimit FROM SUPERVISOR_PROFILE WHERE supervisorID = '${supervisorID}';"
	)

	if (originalQuotaText == '') {
		KeywordUtil.markFailed("Pre-condition failed: supervisor profile not found.")
	}

	originalQuota = originalQuotaText.toInteger()

	String currentSlotsText = runMysql(
		"SELECT COUNT(*) FROM ALLOCATION_RECORD WHERE supervisorID = '${supervisorID}';"
	)

	Integer currentSlots = currentSlotsText.toInteger()

	if (currentSlots <= 0) {
		KeywordUtil.markFailed("Pre-condition failed: supervisor has no allocated students.")
	}

	runMysql(
		"UPDATE SUPERVISOR_PROFILE SET assignedQuotaLimit = ${currentSlots} WHERE supervisorID = '${supervisorID}';"
	)

	WebUI.openBrowser('')
	browserOpened = true
	WebUI.maximizeWindow()

	WebUI.navigateToUrl(baseUrl + '/client/auth/login.php')
	WebUI.setText(emailInput, supervisorEmail)
	WebUI.setText(passwordInput, supervisorPassword)
	WebUI.click(loginButton)
	WebUI.waitForPageLoad(10)

	WebUI.navigateToUrl(baseUrl + '/client/supervisor/supervisorSlotUtilization.php')
	WebUI.waitForPageLoad(10)

	WebUI.verifyElementPresent(pageTitle, 10)
	WebUI.verifyElementPresent(liveUtilization, 10)
	WebUI.verifyElementPresent(slotsFilled, 10)
	WebUI.verifyElementPresent(quotaFilled, 10)
	WebUI.verifyElementPresent(quotaFullyFilledMessage, 10)
	WebUI.verifyElementPresent(allocationHealth, 10)
	WebUI.verifyElementPresent(availableCapacity, 10)

} finally {
	if (originalQuota != null) {
		runMysql(
			"UPDATE SUPERVISOR_PROFILE SET assignedQuotaLimit = ${originalQuota} WHERE supervisorID = '${supervisorID}';"
		)
	}

	if (browserOpened) {
		WebUI.closeBrowser()
	}
}