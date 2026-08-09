import com.kms.katalon.core.testobject.TestObject
import com.kms.katalon.core.testobject.ConditionType
import com.kms.katalon.core.webui.keyword.WebUiBuiltInKeywords as WebUI
import com.kms.katalon.core.model.FailureHandling

// ==============================================================================
// TC001: Real-Time Dashboard - Quota Rendering
// ==============================================================================

// 1. INLINE OBJECT REPOSITORY
// Creating TestObjects dynamically via XPath to avoid relying on a local Object Repository
TestObject emailInput = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//input[@type='email']")
TestObject passwordInput = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//input[@id='passwordInput']")
TestObject loginBtn = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//button[@type='submit' or @class='btn-login']")

// Targets the specific fractional string inside the supervisor discovery card
// Use contains(., 'Supervisor Name') to find the exact card, then dig into its badge.
TestObject quotaBadge = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//article[contains(@class,'discovery-card') and contains(., 'Lee Zi Qing')]//span[contains(@class,'quota-badge')]")
try {
    // Step 1: Navigate to the SSAS login page
    WebUI.openBrowser('')
    WebUI.maximizeWindow()
    WebUI.navigateToUrl('http://localhost/ssas/client/auth/login.php')
    WebUI.waitForElementVisible(emailInput, 10, FailureHandling.STOP_ON_FAILURE)

    // Step 2: Enter student email
    WebUI.setText(emailInput, 'yongcx-wp23@student.tarc.edu.my')

    // Step 3: Enter student password
    WebUI.setText(passwordInput, 'Jasden7181@')

    // Step 4: Click the login button
    WebUI.click(loginBtn)
    
    // Wait a moment for the server to authenticate and load the initial dashboard
    WebUI.waitForPageLoad(15)

    // Step 5: Navigate explicitly to the Student Discovery page
    WebUI.navigateToUrl('http://localhost/ssas/client/student/studentDiscovery.php')
    
    // Wait for the Discovery page to load and the badge to become visible
    WebUI.waitForElementVisible(quotaBadge, 15, FailureHandling.STOP_ON_FAILURE)

    // Step 6: Observe the supervisor cards
    // Retrieve the text rendered on the UI
    String actualQuotaText = WebUI.getText(quotaBadge)
    
    // Assert that the text matches the expected fraction (Note the uppercase AVAILABLE)
    WebUI.verifyMatch(actualQuotaText, '.*AVAILABLE:\\s*3\\s*/\\s*28.*', true, FailureHandling.STOP_ON_FAILURE)
    
    // Post-conditions: The system remains on the Student Discovery page
    WebUI.verifyElementVisible(quotaBadge, FailureHandling.STOP_ON_FAILURE)
    println("TC001 Passed: Real-Time dashboard correctly calculated and rendered the quota.")

} catch (Exception e) {
    // Take a screenshot of the failure state before crashing the test
    WebUI.takeScreenshot()
    println("TC001 Failed: An error occurred during execution.")
    throw e
} finally {
    // Clean up environment
    WebUI.closeBrowser()
}