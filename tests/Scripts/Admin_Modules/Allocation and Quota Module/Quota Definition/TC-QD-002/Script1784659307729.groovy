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

// Login objects
TestObject emailInput = byXpath('emailInput', "//input[@name='email']")
TestObject passwordInput = byXpath('passwordInput', "//input[@name='password']")
TestObject loginButton = byXpath('loginButton', "//button[@type='submit' and contains(@class,'btn-login')]")

// Quota Management objects
TestObject quotaInput5836 = byXpath(
	'quotaInput5836',
	"//article[contains(@class,'quota-row') and @data-row='5836']//input[contains(@class,'quota-input')]"
)

TestObject saveQuotaButton = byXpath(
	'saveQuotaButton',
	"//button[@id='saveButton']"
)

TestObject overCapacityStatus5836 = byXpath(
	'overCapacityStatus5836',
	"//article[contains(@class,'quota-row') and @data-row='5836']//span[@data-status-badge and normalize-space()='Over-Capacity']"
)

// Open browser and login
WebUI.openBrowser('')
WebUI.maximizeWindow()

WebUI.navigateToUrl(baseUrl + '/client/auth/login.php')

WebUI.setText(emailInput, 'admin@tarc.edu.my')
WebUI.setText(passwordInput, 'admin7181!')

WebUI.click(loginButton)
WebUI.waitForPageLoad(10)

// Navigate to Quota Management page
WebUI.navigateToUrl(baseUrl + '/client/admin/quotaManagement.php')
WebUI.waitForPageLoad(10)

WebUI.verifyTextPresent('Quota Management', false)

// Enter invalid quota value that exceeds classification limit
WebUI.clearText(quotaInput5836)
WebUI.setText(quotaInput5836, '999')

// Verify quota status becomes Over-Capacity
WebUI.verifyElementPresent(overCapacityStatus5836, 10)
WebUI.verifyTextPresent('Over-Capacity', false)

// Save button should not be clickable because invalid quota disables it
WebUI.verifyElementNotClickable(saveQuotaButton)

WebUI.closeBrowser()