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
String supervisorPassword = 'leezq1129!'

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

TestObject successMessage = byXpath(
	'successMessage',
	"//*[contains(normalize-space(),'Expertise Updated')]"
)

TestObject selectedAI = byXpath(
	'selectedAI',
	"//*[contains(@class,'selected-pill') and normalize-space()='Artificial Intelligence']"
)

TestObject selectedCybersecurity = byXpath(
	'selectedCybersecurity',
	"//*[contains(@class,'selected-pill') and normalize-space()='Cybersecurity']"
)

WebUI.openBrowser('')
WebUI.maximizeWindow()

// Login as supervisor
WebUI.navigateToUrl(baseUrl + '/client/auth/login.php')
WebUI.setText(emailInput, supervisorEmail)
WebUI.setText(passwordInput, supervisorPassword)
WebUI.click(loginButton)
WebUI.waitForPageLoad(10)

// Go to Expertise & Tags page
WebUI.navigateToUrl(baseUrl + '/client/supervisor/manageExpertiseTags.php')
WebUI.waitForPageLoad(10)

WebUI.verifyElementPresent(pageTitle, 10)

// Select required tags safely
WebUI.executeJavaScript("""
	const wantedTags = ['Artificial Intelligence', 'Cybersecurity'];

	wantedTags.forEach(function(tagName) {
		const labels = Array.from(document.querySelectorAll('label.tag-option'));

		const label = labels.find(function(item) {
			return item.innerText.trim().includes(tagName);
		});

		if (label) {
			const checkbox = label.querySelector("input[type='checkbox']");

			if (checkbox && !checkbox.checked) {
				checkbox.click();
			}
		}
	});
""", null)

WebUI.delay(1)

// Save changes
WebUI.scrollToElement(saveButton, 5)
WebUI.click(saveButton)

// Accept confirmation popup if shown
WebUI.acceptAlert(FailureHandling.OPTIONAL)

WebUI.waitForPageLoad(10)

// Verify success result
WebUI.verifyElementPresent(successMessage, 10)
WebUI.verifyElementPresent(selectedAI, 10)
WebUI.verifyElementPresent(selectedCybersecurity, 10)

WebUI.closeBrowser()