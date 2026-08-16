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

String supervisorEmail = 'leezq1129@tarc.edu.my'
String supervisorPassword = 'LeeZQ7181@'

TestObject emailInput = byXpath('emailInput', "//input[@name='email']")
TestObject passwordInput = byXpath('passwordInput', "//input[@name='password']")
TestObject loginButton = byXpath('loginButton', "//button[@type='submit' and contains(@class,'btn-login')]")

TestObject pageTitle = byXpath(
	'pageTitle',
	"//*[normalize-space()='Expertise & Interests']"
)

TestObject saveButton = byXpath(
	'saveButton',
	"//button[@type='submit' and normalize-space()='Save All Changes']"
)

TestObject anyTagCheckbox = byXpath(
	'anyTagCheckbox',
	"//input[@name='tagIDs[]' and @type='checkbox']"
)

TestObject noExpertiseSelected = byXpath(
	'noExpertiseSelected',
	"//*[normalize-space()='No expertise selected']"
)

WebUI.openBrowser('')
WebUI.maximizeWindow()

WebUI.navigateToUrl(baseUrl + '/client/auth/login.php')
WebUI.setText(emailInput, supervisorEmail)
WebUI.setText(passwordInput, supervisorPassword)
WebUI.click(loginButton)
WebUI.waitForPageLoad(10)

WebUI.navigateToUrl(baseUrl + '/client/supervisor/manageExpertiseTags.php')
WebUI.waitForPageLoad(10)

WebUI.verifyElementPresent(pageTitle, 10)
WebUI.verifyElementPresent(anyTagCheckbox, 10)

// Clear all selected tags
WebUI.executeJavaScript("""
	const checkboxes = document.querySelectorAll("input[name='tagIDs[]'][type='checkbox']");

	checkboxes.forEach(function(checkbox) {
		if (checkbox.checked) {
			checkbox.checked = false;
			checkbox.dispatchEvent(new Event('change', { bubbles: true }));
		}
	});
""", null)

WebUI.delay(1)

WebUI.verifyElementPresent(noExpertiseSelected, 10)

WebUI.scrollToElement(saveButton, 5)
WebUI.click(saveButton)

// Frontend validation alert should appear
WebUI.verifyAlertPresent(5)

String alertText = WebUI.getAlertText()
assert alertText.contains('Please select between 1 and 10 expertise tags.')

WebUI.acceptAlert()

// Page should remain on Expertise & Interests because submission was blocked
WebUI.verifyElementPresent(pageTitle, 10)
WebUI.verifyElementPresent(noExpertiseSelected, 10)

WebUI.closeBrowser()