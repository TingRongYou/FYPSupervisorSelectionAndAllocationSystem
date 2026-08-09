import com.kms.katalon.core.testobject.TestObject
import com.kms.katalon.core.testobject.ConditionType
import com.kms.katalon.core.webui.keyword.WebUiBuiltInKeywords as WebUI
import com.kms.katalon.core.model.FailureHandling

// ==============================================================================
// F2.2 - TC002: Verify Top Matches Recommendation (Tag Intersection Mismatch)
// ==============================================================================

// 1. AUTHENTICATION OBJECTS (Yong Chong Xin)
TestObject emailInput = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//input[@type='email']")
TestObject passwordInput = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//input[@id='passwordInput']")
TestObject loginBtn = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//button[@type='submit' or @class='btn-login']")

// 2. TARGET SUPERVISOR CARD INSIDE TOP MATCHES (Replace 'Target Supervisor Name' with an actual supervisor who has 0 tag matches)
String supervisorName = "Target Supervisor Name"
TestObject nonMatchingTopMatchCard = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//section[contains(@class,'recommendation-panel')]//article[contains(@class,'supervisor-card') and .//h2[contains(text(), '" + supervisorName + "')]]")

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
    WebUI.waitForPageLoad(15)

    // Step 6 & 7: Verify that the non-matching supervisor card is NOT present inside the Top Matches section
    WebUI.verifyElementNotPresent(nonMatchingTopMatchCard, 5, FailureHandling.STOP_ON_FAILURE)
    
    println("F2.2-TC002 Passed: Recommendation algorithm successfully excluded the supervisor with zero matching tags from Top Matches.")

} catch (Exception e) {
    WebUI.takeScreenshot()
    println("F2.2-TC002 Failed: The supervisor was incorrectly surfaced in Top Matches despite having no matching tags.")
    throw e
} finally {
    WebUI.closeBrowser()
}