import com.kms.katalon.core.testobject.TestObject
import com.kms.katalon.core.testobject.ConditionType
import com.kms.katalon.core.webui.keyword.WebUiBuiltInKeywords as WebUI
import com.kms.katalon.core.model.FailureHandling

// ==============================================================================
// Function 6.1 - TC002: Verify Phase Tracker (Negative - Server Time Outside Bounds)
// Decision Table: C1=F -> Expected = E2 (Render inactive/maintenance state)
// ==============================================================================

// 1. AUTHENTICATION OBJECTS
TestObject emailInput = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//input[@type='email']")
TestObject passwordInput = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//input[@id='passwordInput']")
TestObject loginBtn = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//button[@type='submit' or @class='btn-login']")

// 2. TIMELINE WIDGET OBJECTS
TestObject phaseTitle = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//h2[@id='phaseTitle']")
TestObject phaseMessage = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//p[@id='phaseMessage']")

try {
    // Step 1: Login
    WebUI.openBrowser('')
    WebUI.maximizeWindow()
    WebUI.navigateToUrl('http://localhost/ssas/client/auth/login.php')
    WebUI.setText(emailInput, 'rayden-wp23@student.tarc.edu.my')
    WebUI.setText(passwordInput, '50517101237')
    WebUI.click(loginBtn)
    
    // Step 2: Navigate to Timeline
    WebUI.navigateToUrl('http://localhost/ssas/client/student/studentTimeline.php')
    WebUI.waitForPageLoad(15)

    // VERIFICATION: E2 - Assert Inactive/Maintenance UI state
    WebUI.waitForElementVisible(phaseTitle, 10, FailureHandling.STOP_ON_FAILURE)
    
    String status = WebUI.getText(phaseTitle).toLowerCase()
    String message = WebUI.getText(phaseMessage).toLowerCase()
    
    // Check for common inactive indicators
    boolean isInactive = status.contains("inactive") || status.contains("no active") || status.contains("maintenance")
    
    if (isInactive) {
        println("SUCCESS: System rendered inactive phase state correctly for server time outside bounds.")
        println("F6.1-TC002 Passed.")
    } else {
        throw new Exception("F6.1-TC002 Failed: Expected 'Inactive' or 'No Active Phase' state, but found: " + status)
    }

} catch (Exception e) {
    WebUI.takeScreenshot()
    println("F6.1-TC002 Encountered an Error: " + e.getMessage())
    throw e
} finally {
    WebUI.closeBrowser()
}