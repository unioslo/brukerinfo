/**
 * 
 * UiO JavaScript - Chooser
 * 
 */

/*
 * Can not do this with css2: Make the chooser boxes on the same line at equal
 * height.
 */
var equalizeHeightBoxes = function(container, leftName, rightName) {
    var funcEqualizeHeightBox = equalizeHeightBox; //performance
    var leftBoxes = $(container + " ." + leftName);
    var leftBoxesLength = leftBoxes.length;
    for(var i = 0; i < leftBoxesLength; i++) {
        var leftBox = leftBoxes[i];
        var rightBox = $(leftBox).next();
        if(rightBox.length && rightBox.attr("class").indexOf(rightName) != -1) {
            funcEqualizeHeightBox($(leftBox), rightBox);
        }
    }
};

var equalizeHeightBox = function(leftBox, rightBox) {
    var leftBoxHeight = leftBox.height();
    var rightBoxHeight = rightBox.height();
    if(rightBoxHeight > leftBoxHeight) {
        leftBox.css("height", rightBoxHeight + "px");
    } else if (rightBoxHeight < leftBoxHeight) {
        rightBox.css("height", leftBoxHeight + "px");
    }
};

equalizeHeightBoxes(".app-chooser", "app-option", "app-solution");

