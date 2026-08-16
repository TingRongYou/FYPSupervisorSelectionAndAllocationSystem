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

String uniqueSuffix = System.currentTimeMillis().toString()
String projectTitleValue = 'Katalon Test Past Project ' + uniqueSuffix
String completionYearValue = '2025'
String alumniNameValue = 'Test Alumni'
String descriptionValue = 'This project demonstrates a web-based final year project system with database integration, validation, and reporting features.'

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

TestObject successMessage = byXpath(
	'successMessage',
	"//*[contains(normalize-space(),'Update Successful - Your past projects have been successfully updated in the showcase')]"
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

WebUI.verifyTextPresent('Add Past Project', false)

// Fill project details
WebUI.setText(projectTitleInput, projectTitleValue)
WebUI.setText(completionYearInput, completionYearValue)
WebUI.setText(alumniNameInput, alumniNameValue)
WebUI.setText(projectDescriptionTextarea, descriptionValue)

// Manual upload checkpoint
println('Please manually upload the required Past Project PDF and Project Cover Image now.')
println('Automation will continue after 120 seconds.')

WebUI.delay(15)

// Submit form after manual upload
WebUI.scrollToElement(addProjectButton, 5)
WebUI.click(addProjectButton)
WebUI.waitForPageLoad(10)

// Verify result
WebUI.verifyElementPresent(successMessage, 10)
WebUI.verifyTextPresent(projectTitleValue, false)
WebUI.verifyTextPresent(alumniNameValue, false)

WebUI.closeBrowser()