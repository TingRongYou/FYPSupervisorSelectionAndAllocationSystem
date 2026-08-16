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

TestObject pageTitle = byXpath('pageTitle', "//*[normalize-space()='Slot Utilization']")
TestObject liveUtilization = byXpath('liveUtilization', "//*[normalize-space()='Live Utilization']")
TestObject slotsFilled = byXpath('slotsFilled', "//*[contains(normalize-space(),'Slots Filled')]")
TestObject optimalUtilization = byXpath('optimalUtilization', "//*[normalize-space()='Optimal Utilization']")
TestObject weeklySlotTrends = byXpath('weeklySlotTrends', "//*[normalize-space()='Weekly Slot Trends']")
TestObject benchmarking = byXpath('benchmarking', "//*[normalize-space()='Benchmarking']")
TestObject allocationHealth = byXpath('allocationHealth', "//*[normalize-space()='Allocation Health']")
TestObject slotEfficiency = byXpath('slotEfficiency', "//*[normalize-space()='Slot Efficiency']")
TestObject unusedCapacity = byXpath('unusedCapacity', "//*[normalize-space()='Unused Capacity']")
TestObject availableCapacity = byXpath('availableCapacity', "//*[normalize-space()='Available Capacity']")

WebUI.openBrowser('')
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
WebUI.verifyElementPresent(optimalUtilization, 10)
WebUI.verifyElementPresent(weeklySlotTrends, 10)
WebUI.verifyElementPresent(benchmarking, 10)
WebUI.verifyElementPresent(allocationHealth, 10)
WebUI.verifyElementPresent(slotEfficiency, 10)
WebUI.verifyElementPresent(unusedCapacity, 10)
WebUI.verifyElementPresent(availableCapacity, 10)

WebUI.closeBrowser()