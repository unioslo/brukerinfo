/**
 * 
 * UiO JavaScript - Tweaks and fixes for limitations in IE.
 * 
 */

/** Requires jquery to be imported */

/**
 * Since IE doesn't support :last-child (only :first-child) this script should
 * remove the last breadcrumb separator ">".
 * The css equivalent: 
 *  #uio-app-breadcrumb li:last-child:after {
 *      content: "";
 *  }
 */
$("#uio-app-breadcrumb li:last-child").addClass("last-breadcrumb");

