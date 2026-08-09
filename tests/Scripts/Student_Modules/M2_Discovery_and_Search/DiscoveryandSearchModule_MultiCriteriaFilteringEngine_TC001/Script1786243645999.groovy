import com.kms.katalon.core.testobject.TestObject
import com.kms.katalon.core.testobject.ConditionType
import com.kms.katalon.core.webui.keyword.WebUiBuiltInKeywords as WebUI
import com.kms.katalon.core.model.FailureHandling

// ==============================================================================
// F2.1 - TC001: Verify Multi-Criteria Filter (All Conditions Match)
// ==============================================================================

// 1. SHARED AUTHENTICATION OBJECTS
TestObject emailInput = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//input[@type='email']")
TestObject passwordInput = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//input[@id='passwordInput']")
TestObject loginBtn = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//button[@type='submit' or @class='btn-login']")

// 2. DISCOVERY MODULE FILTER OBJECTS (Updated via DOM Scan)
TestObject searchBar = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//input[@id='searchName']")
TestObject programmeDropdown = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//select[@id='programme']")
TestObject interestDropdown = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//select[@id='interestTagID']")
TestObject availableTab = new TestObject().addProperty("xpath", ConditionType.EQUALS, "//button[contains(@class,'availability-tab') and @data-value='Available']")
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

    // Step 6: Enter "Lee Zi Qing" into the Search Bar
    WebUI.waitForElementVisible(searchBar, 10, FailureHandling.STOP_ON_FAILURE)
    WebUI.setText(searchBar, 'Lee Zi Qing')
    
    // Step 7: Select Programme
    WebUI.selectOptionByLabel(programmeDropdown, 'RSW', false, FailureHandling.STOP_ON_FAILURE)
    
    // Step 8: Select Research Interest
    WebUI.selectOptionByLabel(interestDropdown, 'Artificial Intelligence', false, FailureHandling.STOP_ON_FAILURE)
    
    // Step 9: Click the "Available" tab
    WebUI.click(availableTab)
    
    // Step 10: Click "Apply Filter" button
    WebUI.click(applyFiltersBtn)
    
    // Wait for the form to submit via GET request and the page to reload
    WebUI.waitForPageLoad(15)
    
    // Optional: Verify the URL actually appended the query parameters
    String currentUrl = WebUI.getUrl()
    WebUI.verifyMatch(currentUrl, '.*searchName=Lee\\+Zi\\+Qing.*', true, FailureHandling.OPTIONAL)
    
    // Verification: Verify the target supervisor card is retained and displayed on the UI
    WebUI.verifyElementPresent(targetSupervisorCard, 10, FailureHandling.STOP_ON_FAILURE)
    WebUI.verifyElementVisible(targetSupervisorCard, FailureHandling.STOP_ON_FAILURE)
    
    println("F2.1-TC001 Passed: The filtering engine successfully matched all 4 criteria and displayed the supervisor card.")

} catch (Exception e) {
    WebUI.takeScreenshot()
    println("F2.1-TC001 Failed: The target supervisor card was incorrectly filtered out or a locator was not found.")
    throw e
} finally {
    WebUI.closeBrowser()
}