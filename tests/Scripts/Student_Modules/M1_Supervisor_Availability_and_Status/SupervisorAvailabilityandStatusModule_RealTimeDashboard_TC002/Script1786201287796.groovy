import com.kms.katalon.core.testobject.TestObject
import com.kms.katalon.core.testobject.ConditionType
import com.kms.katalon.core.webui.keyword.WebUiBuiltInKeywords as WebUI
import com.kms.katalon.core.model.FailureHandling

// ==============================================================================
// TC002: Dynamic Quota Recalculation (Multi-Role End-to-End)
// ==============================================================================

// 1. SHARED AUTHENTICATION OBJECTS
TestObject emailInput = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//input[@type='email']")
TestObject passwordInput = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//input[@id='passwordInput']")
TestObject loginBtn = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//button[@type='submit' or @class='btn-login']")

// New object for the container to trigger the CSS :hover state
TestObject accountMenuContainer = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//div[contains(@class,'account-menu')]")
TestObject logoutBtn = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//a[contains(@class,'logout-link')]")

// 2. SUPERVISOR MODULE OBJECTS
TestObject viewProposalBtn = new TestObject().addProperty("xpath", ConditionType.EQUALS, "(//a[contains(@class,'link-action') and text()='View Proposal'])[1]")

// New object for the required comments text area
TestObject commentBox = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//textarea[@name='supervisorComment']")
TestObject acceptBtn = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//button[@name='decisionStatus' and @value='Accepted']")

// 3. STUDENT MODULE OBJECTS
// IMPORTANT: Change 'Lee ZQ' to the exact name stored in the database for this supervisor!
TestObject quotaBadge = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//article[contains(@class,'discovery-card') and .//h2[contains(text(), 'Lee Zi Qing')]]//span[contains(@class,'quota-badge')]")

try {
    // -------------------------------------------------------------------------
    // PHASE 1: SUPERVISOR WORKFLOW
    // -------------------------------------------------------------------------
    
    // Step 1: Navigate to the SSAS login page
    WebUI.openBrowser('')
    WebUI.maximizeWindow()
    WebUI.navigateToUrl('http://localhost/ssas/client/auth/login.php')
    WebUI.waitForElementVisible(emailInput, 10, FailureHandling.STOP_ON_FAILURE)

    // Step 2 & 3: Enter supervisor credentials
    WebUI.setText(emailInput, 'leezq1129@tarc.edu.my')
    WebUI.setText(passwordInput, 'LeeZQ7181@')

    // Step 4: Click the login button
    WebUI.click(loginBtn)
    WebUI.waitForPageLoad(15)

    // Step 5: Navigate explicitly to the Incoming Requests page using exact filename
    WebUI.navigateToUrl('http://localhost/ssas/client/supervisor/supervisorIncomingRequests.php')
    WebUI.waitForElementVisible(viewProposalBtn, 15, FailureHandling.STOP_ON_FAILURE)

    // Step 6: Click "View Proposal" button
    WebUI.click(viewProposalBtn)
    
    // Wait for the comments box to be ready
    WebUI.waitForElementVisible(commentBox, 15, FailureHandling.STOP_ON_FAILURE)
    
    // Fill in the required comments to bypass HTML5 validation
    WebUI.setText(commentBox, 'Automated testing: Proposal looks good. Approved.')

    // Step 7: Click "Accept" button
    WebUI.click(acceptBtn)
    
    // Wait for the backend POST request to commit to the database and redirect
    WebUI.delay(3) 

    // Step 8: Logout (Using Hover to reveal the dropdown first)
    WebUI.mouseOver(accountMenuContainer)
    WebUI.waitForElementVisible(logoutBtn, 5, FailureHandling.STOP_ON_FAILURE)
    WebUI.click(logoutBtn)
    
    WebUI.waitForElementVisible(emailInput, 10, FailureHandling.STOP_ON_FAILURE)


    // -------------------------------------------------------------------------
    // PHASE 2: STUDENT WORKFLOW
    // -------------------------------------------------------------------------

    // Step 9 & 10: Enter student credentials
    WebUI.setText(emailInput, 'yongcx-wp23@student.tarc.edu.my')
    WebUI.setText(passwordInput, 'Jasden7181@')

    // Step 11: Click the login button
    WebUI.click(loginBtn)
    WebUI.waitForPageLoad(15)

    // Step 12: Navigate to Student Discovery page
    WebUI.navigateToUrl('http://localhost/ssas/client/student/studentDiscovery.php')
    WebUI.waitForElementVisible(quotaBadge, 15, FailureHandling.STOP_ON_FAILURE)

    // Step 13: Observe the quota string on the target supervisor card
    String actualQuotaText = WebUI.getText(quotaBadge)
    
    // Assert that the text dynamically recalculated to X+1 (Note the uppercase AVAILABLE)
    WebUI.verifyMatch(actualQuotaText, '.*AVAILABLE:\\s*5\\s*/\\s*28.*', true, FailureHandling.STOP_ON_FAILURE)    
    println("TC002 Passed: Dashboard successfully recalculated quota after proposal acceptance.")

} catch (Exception e) {
    WebUI.takeScreenshot()
    println("TC002 Failed: An error occurred during execution.")
    throw e
} finally {
    WebUI.closeBrowser()
}