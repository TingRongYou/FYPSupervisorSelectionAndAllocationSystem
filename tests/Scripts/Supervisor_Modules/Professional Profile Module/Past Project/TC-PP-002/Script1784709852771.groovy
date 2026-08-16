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

String projectTitleValue = 'Negative Missing PDF Project'
String completionYearValue = '2025'
String alumniNameValue = 'Test Alumni'
String descriptionValue = 'This project has valid text information and cover image, but the required PDF is intentionally missing.'

TestObject emailInput = byXpath('emailInput', "//input[@name='email']")
TestObject passwordInput = byXpath('passwordInput', "//input[@name='password']")
TestObject loginButton = byXpath('loginButton', "//button[@type='submit' and contains(@class,'btn-login')]")

TestObject addNewProjectButton = byXpath(
	'addNewProjectButton',
	"//a[contains(@href,'addProject=1') and contains(normalize-space(),'Add New Project')]"
)

TestObject projectTitleInput = byXpath('projectTitleInput', "//input[@name='projectTitle']")
TestObject completionYearInput = byXpath('completionYearInput', "//input[@name='completionYear']")
TestObject alumniNameInput = byXpath('alumniNameInput', "//input[@name='alumniName']")
TestObject projectDescriptionTextarea = byXpath('projectDescriptionTextarea', "//textarea[@name='projectDescription']")
TestObject addProjectButton = byXpath('addProjectButton', "//button[@type='submit' and normalize-space()='Add Project']")
TestObject addProjectHeading = byXpath('addProjectHeading', "//*[normalize-space()='Add Past Project']")

TestObject successMessage = byXpath(
	'successMessage',
	"//*[contains(normalize-space(),'Update Successful - Your past projects')]"
)

WebUI.openBrowser('')
WebUI.maximizeWindow()

// Login as supervisor
WebUI.navigateToUrl(baseUrl + '/client/auth/login.php')
WebUI.setText(emailInput, supervisorEmail)
WebUI.setText(passwordInput, supervisorPassword)
WebUI.click(loginButton)
WebUI.waitForPageLoad(10)

// Open Past Projects page
WebUI.navigateToUrl(baseUrl + '/client/supervisor/managePastProjects.php')
WebUI.waitForPageLoad(10)

// Open Add Project form
WebUI.click(addNewProjectButton)
WebUI.waitForPageLoad(10)

WebUI.verifyElementPresent(addProjectHeading, 10)

// Fill required text fields
WebUI.setText(projectTitleInput, projectTitleValue)
WebUI.setText(completionYearInput, completionYearValue)
WebUI.setText(alumniNameInput, alumniNameValue)
WebUI.setText(projectDescriptionTextarea, descriptionValue)

// Manual upload checkpoint
println('Please manually upload the Project Cover Image only.')
println('Do NOT upload the Past Project PDF.')
println('Automation will continue after 120 seconds.')

WebUI.delay(15)

// Submit form with missing PDF
WebUI.scrollToElement(addProjectButton, 5)
WebUI.click(addProjectButton)

WebUI.delay(1)

// Browser required-field validation should block submission because PDF is missing.
Boolean pdfInvalid = WebUI.executeJavaScript(
	"return !document.getElementById('projectPDF').checkValidity();",
	null
)

assert pdfInvalid == true

// Page should remain on Add Past Project form and no success message should be shown.
WebUI.verifyElementPresent(addProjectHeading, 10)
WebUI.verifyElementNotPresent(successMessage, 3, FailureHandling.OPTIONAL)

WebUI.closeBrowser()