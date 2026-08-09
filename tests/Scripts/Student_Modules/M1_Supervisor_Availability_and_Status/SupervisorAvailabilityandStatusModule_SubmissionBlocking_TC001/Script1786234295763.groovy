import com.kms.katalon.core.testobject.TestObject
import com.kms.katalon.core.testobject.ConditionType
import com.kms.katalon.core.webui.keyword.WebUiBuiltInKeywords as WebUI
import com.kms.katalon.core.model.FailureHandling

// ==============================================================================
// F1.3 - TC001: Enable Application Submission (All Conditions Met)
// ==============================================================================

// 1. SHARED AUTHENTICATION OBJECTS
TestObject emailInput = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//input[@type='email']")
TestObject passwordInput = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//input[@id='passwordInput']")
TestObject loginBtn = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//button[@type='submit' or @class='btn-login']")

// 2. DISCOVERY MODULE OBJECTS
TestObject viewProfileBtn = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//article[contains(@class,'discovery-card') and .//h2[contains(text(), 'Lee Zi Qing')]]//a[contains(text(), 'View Profile')]")

// 3. PROFILE MODULE OBJECTS (Patched based on DOM scan)
// Enabled state: a real link pointing to the submission form
TestObject submitProposalLink = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//a[contains(@class,'button') and contains(@href,'submitProposalForm.php')]")

// Blocked state: a disabled span taking the visual place of the button
TestObject disabledActionSpan = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//span[contains(@class,'button') and contains(@class,'disabled')]")

try {
    // Step 1: Navigate to the SSAS login page
    WebUI.openBrowser('')
    WebUI.maximizeWindow()
    WebUI.navigateToUrl('http://localhost/ssas/client/auth/login.php')
    WebUI.waitForElementVisible(emailInput, 10, FailureHandling.STOP_ON_FAILURE)

    // Step 2 & 3: Enter student credentials
    WebUI.setText(emailInput, 'marshell-wp23@student.tarc.edu.my')
    WebUI.setText(passwordInput, '51117103447')

    // Step 4: Click the login button
    WebUI.click(loginBtn)
    WebUI.waitForPageLoad(15)

    // Step 5: Navigate explicitly to the Student Discovery page
    WebUI.navigateToUrl('http://localhost/ssas/client/student/studentDiscovery.php')
    WebUI.waitForElementVisible(viewProfileBtn, 15, FailureHandling.STOP_ON_FAILURE)

    // Step 6: Click "View Profile" for the target supervisor
    WebUI.click(viewProfileBtn)
    WebUI.waitForPageLoad(15)
    
    // Step 7: Verify the pipeline is NOT blocked
    // Ensure the system did not render the fallback/disabled span
    WebUI.verifyElementNotPresent(disabledActionSpan, 2, FailureHandling.STOP_ON_FAILURE)

    // Wait for the active Submit Proposal link to render
    WebUI.waitForElementVisible(submitProposalLink, 10, FailureHandling.STOP_ON_FAILURE)
    
    // Step 8: Verify the link is interactable and click it
    WebUI.verifyElementClickable(submitProposalLink)
    WebUI.click(submitProposalLink)
    
    // Wait for the application form page to load to confirm the pipeline is open
    WebUI.waitForPageLoad(15)
    
    // Verify the URL successfully changed to the application form
    String currentUrl = WebUI.getUrl()
    WebUI.verifyMatch(currentUrl, '.*submitProposalForm.*', true, FailureHandling.OPTIONAL)
    
    println("F1.3-TC001 Passed: System successfully evaluated all conditions as True and enabled the Application pipeline.")

} catch (Exception e) {
    WebUI.takeScreenshot()
    println("F1.3-TC001 Failed: The Application pipeline was blocked. Verify the system phase dates in the database!")
    throw e
} finally {
    WebUI.closeBrowser()
}