import com.kms.katalon.core.testobject.TestObject
import com.kms.katalon.core.testobject.ConditionType
import com.kms.katalon.core.webui.keyword.WebUiBuiltInKeywords as WebUI
import com.kms.katalon.core.model.FailureHandling

// ==============================================================================
// F3.2 - TC003: Verify Status Tracking (Empty State Render)
// ==============================================================================

// 1. AUTHENTICATION OBJECTS (Marshell)
TestObject emailInput = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//input[@type='email']")
TestObject passwordInput = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//input[@id='passwordInput']")
TestObject loginBtn = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//button[@type='submit' or @class='btn-login']")

// 2. EMPTY STATE & REDIRECT OBJECTS
TestObject emptyStateContainer = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//*[contains(@class,'empty-state') or contains(@class,'empty-grid-card')]")
TestObject browseSupervisorBtn = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//a[contains(@href,'studentDiscovery.php') or contains(text(),'Browse Supervisor') or contains(text(),'Discovery')]")

try {
    // Step 1: Navigate to login page
    WebUI.openBrowser('')
    WebUI.maximizeWindow()
    WebUI.navigateToUrl('http://localhost/ssas/client/auth/login.php')
    WebUI.waitForElementVisible(emailInput, 10, FailureHandling.STOP_ON_FAILURE)

    // Step 2 & 3: Enter student credentials
    WebUI.setText(emailInput, 'marshell-wp23@student.tarc.edu.my')
    WebUI.setText(passwordInput, '51117103447')

    // Step 4: Click login
    WebUI.click(loginBtn)
    WebUI.waitForPageLoad(15)

    // Step 5: Navigate to Application Status page
    WebUI.navigateToUrl('http://localhost/ssas/client/student/studentApplicationStatus.php')
    WebUI.waitForPageLoad(15)

    // VERIFICATION 1: Render the visual "Empty State" graphic and "Browse Supervisor" button
    WebUI.waitForElementVisible(emptyStateContainer, 10, FailureHandling.STOP_ON_FAILURE)
    WebUI.verifyElementVisible(emptyStateContainer)
    WebUI.waitForElementVisible(browseSupervisorBtn, 5, FailureHandling.STOP_ON_FAILURE)
    WebUI.verifyElementVisible(browseSupervisorBtn)

    // Step 6: Click "Browse Supervisor" button and verify redirection
    WebUI.click(browseSupervisorBtn)
    WebUI.waitForPageLoad(15)
    
    String currentUrl = WebUI.getUrl()
    if (!currentUrl.contains("studentDiscovery.php")) {
        throw new Exception("Redirection failed. Expected to land on studentDiscovery.php but current URL is: " + currentUrl)
    }

    println("F3.2-TC003 Passed: Empty state rendered correctly and successfully redirected to Student Discovery page.")

} catch (Exception e) {
    WebUI.takeScreenshot()
    println("F3.2-TC003 Failed: Empty state view or redirection link failed to behave as expected.")
    throw e
} finally {
    WebUI.closeBrowser()
}