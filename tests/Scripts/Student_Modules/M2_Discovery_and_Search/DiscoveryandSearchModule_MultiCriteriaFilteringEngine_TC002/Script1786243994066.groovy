import com.kms.katalon.core.testobject.TestObject
import com.kms.katalon.core.testobject.ConditionType
import com.kms.katalon.core.webui.keyword.WebUiBuiltInKeywords as WebUI
import com.kms.katalon.core.model.FailureHandling

// ==============================================================================
// F2.1 - TC002: Verify Multi-Criteria Filter (Search Text Mismatch)
// ==============================================================================

// 1. SHARED AUTHENTICATION OBJECTS
TestObject emailInput = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//input[@type='email']")
TestObject passwordInput = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//input[@id='passwordInput']")
TestObject loginBtn = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//button[@type='submit' or @class='btn-login']")

// 2. DISCOVERY MODULE FILTER OBJECTS
TestObject searchBar = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//input[@id='searchName']")
TestObject applyFiltersBtn = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//button[@type='submit' and contains(text(),'Apply Filters')]")

// 3. TARGET SUPERVISOR CARD
TestObject targetSupervisorCard = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//article[contains(@class,'discovery-card') and .//h2[contains(text(), 'Lee Zi Qing')]]")

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
    WebUI.waitForPageLoad(15)

    // Step 6: Enter “Lee Zi Qang” into the Search Bar
    WebUI.waitForElementVisible(searchBar, 10, FailureHandling.STOP_ON_FAILURE)
    WebUI.setText(searchBar, 'Lee Zi Qang')
    
    // Step 7: Click "Apply Filter" button
    WebUI.click(applyFiltersBtn)
    
    // Wait for the form to submit via GET request and the page to reload
    WebUI.waitForPageLoad(15)
    
    // VERIFICATION: Verify the target supervisor card is NOT displayed on the UI
    WebUI.verifyElementNotPresent(targetSupervisorCard, 5, FailureHandling.STOP_ON_FAILURE)
    
    println("F2.1-TC002 Passed: The filtering engine correctly evaluated the search mismatch and removed the supervisor card.")

} catch (Exception e) {
    WebUI.takeScreenshot()
    println("F2.1-TC002 Failed: The target supervisor card was incorrectly displayed despite the name mismatch.")
    throw e
} finally {
    WebUI.closeBrowser()
}