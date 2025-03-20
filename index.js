const puppeteer = require('puppeteer');
// Or import puppeteer from 'puppeteer-core';

const start = async () => {
    // Launch the browser and open a new blank page
    const browser = await puppeteer.launch();
    const page = await browser.newPage();

    // Navigate the page to a URL.
    await page.goto('http://mhiptv.info:2095/live/giro069/2243768906/22.m3u8');

    // Set screen size.
    await page.setViewport({width: 1080, height: 1024});

    await page.screenshot({ path: 'fullpage.png', fullPage: true });
    
    browser.close();
}

start();