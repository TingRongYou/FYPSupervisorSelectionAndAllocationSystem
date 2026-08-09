import com.kms.katalon.core.testobject.TestObject
import com.kms.katalon.core.testobject.ConditionType
import com.kms.katalon.core.webui.keyword.WebUiBuiltInKeywords as WebUI
import com.kms.katalon.core.model.FailureHandling

// ==============================================================================
// F2.2 - TC001: Verify Top Matches Recommendation (All Conditions Met)
// ==============================================================================

// 1. AUTHENTICATION OBJECTS (Yong Chong Xin)
TestObject emailInput = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//input[@type='email']")
TestObject passwordInput = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//input[@id='passwordInput']")
TestObject loginBtn = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//button[@type='submit' or @class='btn-login']")

// 2. TOP MATCHES UI OBJECTS (Precise DOM structures)
TestObject topMatchSupervisorCard = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//section[contains(@class,'recommendation-panel')]//article[contains(@class,'supervisor-card') and not(contains(@class,'empty-grid-card')) and .//h2[contains(text(), 'Lee Zi Qing')]]")
TestObject matchScoreElement = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//section[contains(@class,'recommendation-panel')]//article[contains(@class,'supervisor-card') and .//h2[contains(text(), 'Lee Zi Qing')]]//span[@class='match-score']")

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

    // Step 6 & 7: Verify that Lee Zi Qing's card is present inside the Top Matches section
    WebUI.waitForElementPresent(topMatchSupervisorCard, 10, FailureHandling.STOP_ON_FAILURE)
    WebUI.verifyElementVisible(topMatchSupervisorCard, FailureHandling.STOP_ON_FAILURE)
    
    // Verify the match score label ("N Match(es)") is actively rendered
    WebUI.verifyElementVisible(matchScoreElement, FailureHandling.STOP_ON_FAILURE)
    String scoreText = WebUI.getText(matchScoreElement)
    
    println("F2.2-TC001 Passed: Supervisor Lee Zi Qing surfaced in Top Matches with score display: '" + scoreText + "'.")

} catch (Exception e) {
    WebUI.takeScreenshot()
    println("F2.2-TC001 Failed: The target supervisor card or match score was not found in the Top Matches section.")
    throw e
} finally {
    WebUI.closeBrowser()
}