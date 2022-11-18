module.exports = async (page, scenario) => {
    const hoverSelector = scenario.hoverSelectors || scenario.hoverSelector;
    const clickSelector = scenario.clickSelectors || scenario.clickSelector;
    const keyPressSelector =
        scenario.keyPressSelectors || scenario.keyPressSelector;
    const scrollToSelector = scenario.scrollToSelector;
    const postInteractionWait = scenario.postInteractionWait; // selector [str] | ms [int]
    const addAttribute = scenario.addAttributes || scenario.addAttribute;

    if (addAttribute) {
        for (const item of [].concat(addAttribute)) {
            if (typeof item === 'object' && item.hasOwnProperty('selector') && item.hasOwnProperty('attribute')) {
                await page.waitForSelector(item.selector);
                await page.evaluate((item) => {
                    document.querySelectorAll(item.selector).forEach(el => {
                        const name = item.attribute.name;
                        const value = item.attribute.value;
                        const delimiter = item.delimiter || ' ';
                        if (el.getAttribute(name)) {
                            const prevValue = el.getAttribute(name);
                            el.setAttribute(name, prevValue + delimiter + value);
                        } else {
                            el.setAttribute(name, value);
                        }
                    });
                }, item);
            } else {
                throw new Error('addAttribute must be an object: {selector: "", attribute: {name: "", value: ""}}');
            }
        }
    }

    if (keyPressSelector) {
        for (const keyPressSelectorItem of [].concat(keyPressSelector)) {
            await page.waitForSelector(keyPressSelectorItem.selector);
            await page.type(
                keyPressSelectorItem.selector,
                keyPressSelectorItem.keyPress
            );
        }
    }

    if (hoverSelector) {
        for (const hoverSelectorIndex of [].concat(hoverSelector)) {
            await page.waitForSelector(hoverSelectorIndex);
            await page.hover(hoverSelectorIndex);
        }
    }

    if (clickSelector) {
        for (const clickSelectorIndex of [].concat(clickSelector)) {
            if (typeof clickSelectorIndex === 'object') {
                if (typeof clickSelectorIndex.waitFor === 'number') {
                    await page.waitForTimeout(clickSelectorIndex.waitFor);
                }
            } else {
                await page.waitForSelector(clickSelectorIndex);
                await page.click(clickSelectorIndex);
            }
        }
    }

    if (postInteractionWait) {
        if (typeof postInteractionWait === 'number') {
            await page.waitForTimeout(postInteractionWait);
        } else {
            await page.waitForSelector(postInteractionWait);
        }
    }

    if (scrollToSelector) {
        await page.waitForSelector(scrollToSelector);
        await page.evaluate((scrollToSelector) => {
            document.querySelector(scrollToSelector).scrollIntoView();
        }, scrollToSelector);
    }
};
