import com.kms.katalon.core.webui.keyword.WebUiBuiltInKeywords as WebUI
import com.kms.katalon.core.testobject.TestObject
import com.kms.katalon.core.testobject.ConditionType
import com.kms.katalon.core.model.FailureHandling as FailureHandling

TestObject byXpath(String name, String xpath) {
	TestObject obj = new TestObject(name)
	obj.addProperty('xpath', ConditionType.EQUALS, xpath)
	return obj
}

String baseUrl = 'http://localhost/ssas'
String supervisorEmail = 'barcola@tarc.edu.my'
String supervisorPassword = 'barcola7777'

// Choose a future year that has no demographic records.
String noRecordYear = '2026'

TestObject emailInput = byXpath('emailInput', "//input[@name='email']")
TestObject passwordInput = byXpath('passwordInput', "//input[@name='password']")
TestObject loginButton = byXpath('loginButton', "//button[@type='submit' and contains(@class,'btn-login')]")

TestObject pageTitle = byXpath('pageTitle', "//*[normalize-space()='Applicant Demographics']")
TestObject programmeDistribution = byXpath('programmeDistribution', "//*[normalize-space()='Programme Distribution']")
TestObject emptyMessage = byXpath('emptyMessage', "//*[contains(@class,'empty-message')]")
TestObject donutChart = byXpath('donutChart', "//*[contains(@class,'donut')]")

WebUI.openBrowser('')
WebUI.maximizeWindow()

WebUI.navigateToUrl(baseUrl + '/client/auth/login.php')
WebUI.setText(emailInput, supervisorEmail)
WebUI.setText(passwordInput, supervisorPassword)
WebUI.click(loginButton)
WebUI.waitForPageLoad(10)

WebUI.navigateToUrl(baseUrl + '/client/supervisor/supervisorApplicantDemographics.php?year=' + noRecordYear)
WebUI.waitForPageLoad(10)

WebUI.verifyElementPresent(pageTitle, 10)
WebUI.verifyElementPresent(programmeDistribution, 10)
WebUI.verifyElementPresent(emptyMessage, 10)
WebUI.verifyElementNotPresent(donutChart, 3, FailureHandling.OPTIONAL)

WebUI.closeBrowser()