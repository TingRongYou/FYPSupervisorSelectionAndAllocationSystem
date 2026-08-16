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

TestObject pageTitle = byXpath('pageTitle', "//*[normalize-space()='Applicant Demographics']")
TestObject programmeDistribution = byXpath('programmeDistribution', "//*[normalize-space()='Programme Distribution']")
TestObject donutChart = byXpath('donutChart', "//*[contains(@class,'donut')]")
TestObject totalApplicants = byXpath('totalApplicants', "//*[normalize-space()='Total Applicants']")
TestObject legendRow = byXpath('legendRow', "(//*[contains(@class,'legend-row')])[1]")
TestObject percentageValue = byXpath('percentageValue', "(//*[contains(@class,'legend-pct')])[1]")

WebUI.openBrowser('')
WebUI.maximizeWindow()

WebUI.navigateToUrl(baseUrl + '/client/auth/login.php')
WebUI.setText(emailInput, supervisorEmail)
WebUI.setText(passwordInput, supervisorPassword)
WebUI.click(loginButton)
WebUI.waitForPageLoad(10)

WebUI.navigateToUrl(baseUrl + '/client/supervisor/supervisorApplicantDemographics.php')
WebUI.waitForPageLoad(10)

WebUI.verifyElementPresent(pageTitle, 10)
WebUI.verifyElementPresent(programmeDistribution, 10)
WebUI.verifyElementPresent(donutChart, 10)
WebUI.verifyElementPresent(totalApplicants, 10)
WebUI.verifyElementPresent(legendRow, 10)
WebUI.verifyElementPresent(percentageValue, 10)

WebUI.verifyTextPresent('applicant', false)
WebUI.verifyTextPresent('%', false)

WebUI.closeBrowser()