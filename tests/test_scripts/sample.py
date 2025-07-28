# 示例Python Selenium脚本
from selenium import webdriver
from selenium.webdriver.common.by import By
import time

driver.find_element(By.ID, "username").send_keys("testuser")
driver.find_element(By.ID, "password").send_keys("testpass")
driver.find_element(By.ID, "login-button").click()

time.sleep(2)

driver.find_element(By.ID, "dashboard-link").click()

WebDriverWait(driver, 10).until(EC.presence_of_element_located((By.ID, "welcome-message")))
