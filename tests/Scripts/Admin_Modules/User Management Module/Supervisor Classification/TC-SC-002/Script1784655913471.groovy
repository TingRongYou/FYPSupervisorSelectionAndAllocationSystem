import static com.kms.katalon.core.checkpoint.CheckpointFactory.findCheckpoint
import static com.kms.katalon.core.testcase.TestCaseFactory.findTestCase
import static com.kms.katalon.core.testdata.TestDataFactory.findTestData
import static com.kms.katalon.core.testobject.ObjectRepository.findTestObject
import static com.kms.katalon.core.testobject.ObjectRepository.findWindowsObject
import com.kms.katalon.core.checkpoint.Checkpoint as Checkpoint
import com.kms.katalon.core.cucumber.keyword.CucumberBuiltinKeywords as CucumberKW
import com.kms.katalon.core.mobile.keyword.MobileBuiltInKeywords as Mobile
import com.kms.katalon.core.model.FailureHandling as FailureHandling
import com.kms.katalon.core.testcase.TestCase as TestCase
import com.kms.katalon.core.testdata.TestData as TestData
import com.kms.katalon.core.testng.keyword.TestNGBuiltinKeywords as TestNGKW
import com.kms.katalon.core.testobject.TestObject as TestObject
import com.kms.katalon.core.webservice.keyword.WSBuiltInKeywords as WS
import com.kms.katalon.core.webui.keyword.WebUiBuiltInKeywords as WebUI
import com.kms.katalon.core.windows.keyword.WindowsBuiltinKeywords as Windows
import internal.GlobalVariable as GlobalVariable
import org.openqa.selenium.Keys as Keys

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

TestObject emailInput = byXpath('emailInput', "//input[@name='email']")
TestObject passwordInput = byXpath('passwordInput', "//input[@name='password']")
TestObject loginButton = byXpath('loginButton', "//button[@type='submit' and contains(@class,'btn-login')]")

TestObject barcolaClassification = byXpath(
	'barcolaClassification',
	"//form[contains(@class,'data-row')][.//input[@name='supervisorID' and @value='5836']]//select[@name='employmentCategory']"
)

TestObject barcolaSaveButton = byXpath(
	'barcolaSaveButton',
	"//form[contains(@class,'data-row')][.//input[@name='supervisorID' and @value='5836']]//button[@type='submit']"
)

WebUI.openBrowser('')
WebUI.maximizeWindow()

WebUI.navigateToUrl(baseUrl + '/client/auth/login.php')

WebUI.setText(emailInput, 'admin@tarc.edu.my')

// Use plain password first. Your encrypted password may not match.
WebUI.setText(passwordInput, 'admin7181!')

WebUI.click(loginButton)

// Go directly to Supervisor Management after login
WebUI.navigateToUrl(baseUrl + '/client/admin/supervisorsManagement.php')

WebUI.waitForPageLoad(10)
WebUI.verifyTextPresent('Supervisor Directory', false)
// Negative Test Case: classification and quota tier mismatch
WebUI.selectOptionByLabel(barcolaClassification, 'Full-Time Lecturer', false)

// Force wrong hidden quotaID after dropdown selection.
// Full-Time should use quotaID 1, so quotaID 2 creates mismatch.
WebUI.executeJavaScript(
	"document.querySelector('form.data-row input[name=\"supervisorID\"][value=\"5836\"]').closest('form').querySelector('input[name=\"quotaID\"]').value = '2';",
	null
)

WebUI.click(barcolaSaveButton)

WebUI.waitForPageLoad(10)
WebUI.verifyTextPresent('Selected quota tier does not match the supervisor classification', false)