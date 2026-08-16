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

String validVideoUrl = 'https://youtu.be/iI34LYmJ1Fs?si=_ssHbfvB8vdbTF5z'
String videoDescription = 'This introductory video explains my supervision approach, research interests, and expectations for final year project students.'

TestObject emailInput = byXpath('emailInput', "//input[@name='email']")
TestObject passwordInput = byXpath('passwordInput', "//input[@name='password']")
TestObject loginButton = byXpath('loginButton', "//button[@type='submit' and contains(@class,'btn-login')]")

TestObject pageTitle = byXpath(
	'pageTitle',
	"//*[normalize-space()='Introductory Video']"
)

TestObject externalTab = byXpath(
	'externalTab',
	"//label[@id='externalTab']"
)

TestObject introVideoLinkInput = byXpath(
	'introVideoLinkInput',
	"//input[@name='introVideoLink']"
)

TestObject descriptionTextarea = byXpath(
	'descriptionTextarea',
	"//textarea[@name='introVideoDescription']"
)

TestObject publishButton = byXpath(
	'publishButton',
	"//button[@type='submit' and normalize-space()='Publish Video']"
)

TestObject successMessage = byXpath(
	'successMessage',
	"//*[contains(normalize-space(),'Update Successful - Your introductory video')]"
)

TestObject publishedStatus = byXpath(
	'publishedStatus',
	"//*[contains(normalize-space(),'Status: Published')]"
)

WebUI.openBrowser('')
WebUI.maximizeWindow()

WebUI.navigateToUrl(baseUrl + '/client/auth/login.php')
WebUI.setText(emailInput, supervisorEmail)
WebUI.setText(passwordInput, supervisorPassword)
WebUI.click(loginButton)
WebUI.waitForPageLoad(10)

WebUI.navigateToUrl(baseUrl + '/client/supervisor/manageIntroVideo.php')
WebUI.waitForPageLoad(10)

WebUI.verifyElementPresent(pageTitle, 10)

// Click styled External Link tab instead of hidden radio input
WebUI.click(externalTab)

WebUI.executeJavaScript("""
	const externalRadio = document.querySelector("input[name='contentSource'][value='external']");
	const savedLinkPill = document.getElementById('savedLinkPill');
	const urlWrap = document.getElementById('urlWrap');
	const linkInput = document.getElementById('introVideoLink');

	if (externalRadio) {
		externalRadio.checked = true;
		externalRadio.dispatchEvent(new Event('change', { bubbles: true }));
	}

	if (savedLinkPill) {
		savedLinkPill.style.display = 'none';
	}

	if (urlWrap) {
		urlWrap.style.display = 'block';
	}

	if (linkInput) {
		linkInput.disabled = false;
	}
""", null)

WebUI.delay(1)

WebUI.clearText(introVideoLinkInput)
WebUI.setText(introVideoLinkInput, validVideoUrl)

WebUI.clearText(descriptionTextarea)
WebUI.setText(descriptionTextarea, videoDescription)

WebUI.scrollToElement(publishButton, 5)
WebUI.click(publishButton)

WebUI.acceptAlert(FailureHandling.OPTIONAL)
WebUI.waitForPageLoad(10)

WebUI.verifyElementPresent(successMessage, 10)
WebUI.verifyElementPresent(publishedStatus, 10)

WebUI.closeBrowser()