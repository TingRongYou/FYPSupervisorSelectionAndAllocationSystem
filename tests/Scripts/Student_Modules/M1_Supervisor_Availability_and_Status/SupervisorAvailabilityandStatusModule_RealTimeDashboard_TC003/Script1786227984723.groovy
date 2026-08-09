import com.kms.katalon.core.testobject.TestObject
import com.kms.katalon.core.testobject.ConditionType
import com.kms.katalon.core.webui.keyword.WebUiBuiltInKeywords as WebUI
import com.kms.katalon.core.model.FailureHandling

// ==============================================================================
// TC003: Invalid Quota Data Handling (Fallback State)
// ==============================================================================

// 1. SHARED AUTHENTICATION OBJECTS
TestObject emailInput = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//input[@type='email']")
TestObject passwordInput = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//input[@id='passwordInput']")
TestObject loginBtn = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//button[@type='submit' or @class='btn-login']")

// 2. STUDENT MODULE OBJECTS
// IMPORTANT: Change 'Target Supervisor Name' to the EXACT name of the supervisor you corrupted in the database!
TestObject quotaBadge = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//article[contains(@class,'discovery-card') and .//h2[contains(text(), 'Dracola')]]//span[contains(@class,'quota-badge')]")
try {
    // Step 1: Navigate to the SSAS login page
    WebUI.openBrowser('')
    WebUI.maximizeWindow()
    WebUI.navigateToUrl('http://localhost/ssas/client/auth/login.php')
    WebUI.waitForElementVisible(emailInput, 10, FailureHandling.STOP_ON_FAILURE)

    // Step 2 & 3: Enter student credentials
    WebUI.setText(emailInput, 'yongcx-wp23@student.tarc.edu.my')
    WebUI.setText(passwordInput, 'Jasden7181@')

    // Step 4: Click the login button
    WebUI.click(loginBtn)
    WebUI.waitForPageLoad(15)

    // Step 5: Navigate explicitly to the Student Discovery page
    WebUI.navigateToUrl('http://localhost/ssas/client/student/studentDiscovery.php')
    WebUI.waitForElementVisible(quotaBadge, 15, FailureHandling.STOP_ON_FAILURE)

    // Step 6: Observe the quota string on the target corrupted supervisor card
    String actualQuotaText = WebUI.getText(quotaBadge)
    
    // Assert that the system gracefully degraded to the 0 / 0 fallback state (and changed status to FULL)
    WebUI.verifyMatch(actualQuotaText, '.*FULL:\\s*0\\s*/\\s*0.*', true, FailureHandling.STOP_ON_FAILURE)
    
    println("TC003 Passed: System gracefully handled missing quota data without crashing, rendering FULL: 0 / 0.")

} catch (Exception e) {
    WebUI.takeScreenshot()
    println("TC003 Failed: An error occurred during execution. Check if the backend threw a fatal error.")
    throw e
} finally {
    WebUI.closeBrowser()
}