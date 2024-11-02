/*
 * Copyright © 2022 by Biblica, Inc. (https://www.biblica.com)
 * Licensed under MIT (https://opensource.org/licenses/MIT)
 */

window.fumsData = window.fumsData || [];
window.fums = window.fums || function () {
    window.fumsData.push(arguments);
};
if (window.fumsTokens) {
    fums('trackView', window.fumsTokens);
    console.log('fums("trackView", ' + JSON.stringify(window.fumsTokens) + ')');
}
