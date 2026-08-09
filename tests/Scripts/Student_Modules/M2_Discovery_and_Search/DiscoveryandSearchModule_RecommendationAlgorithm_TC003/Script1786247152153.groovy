import com.kms.katalon.core.testobject.TestObject
import com.kms.katalon.core.testobject.ConditionType
import com.kms.katalon.core.webui.keyword.WebUiBuiltInKeywords as WebUI
import com.kms.katalon.core.model.FailureHandling

// ==============================================================================
// F2.2 - TC003: Verify Top Matches Recommendation (Unavailable Supervisor Exclusion)
// ==============================================================================

// 1. AUTHENTICATION OBJECTS (Yong Chong Xin)
TestObject emailInput = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//input[@type='email']")
TestObject passwordInput = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//input[@id='passwordInput']")
TestObject loginBtn = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//button[@type='submit' or @class='btn-login']")

// 2. TARGET SUPERVISOR CARD INSIDE TOP MATCHES (Targeting Robin)
TestObject robinTopMatchCard = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//section[contains(@class,'recommendation-panel')]//article[contains(@class,'supervisor-card') and .//h2[contains(text(), 'Robin')]]")

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

    // Step 6 & 7: Verify that Robin's card is NOT present inside the Top Matches section
    WebUI.verifyElementNotPresent(robinTopMatchCard, 5, FailureHandling.STOP_ON_FAILURE)
    
    println("F2.2-TC003 Passed: Recommendation algorithm successfully excluded the unavailable supervisor (Robin) from Top Matches.")

} catch (Exception e) {
    WebUI.takeScreenshot()
    println("F2.2-TC003 Failed: Robin was incorrectly surfaced in Top Matches despite being unavailable.")
    throw e
} finally {
    WebUI.closeBrowser()
}