/**
 * @package   BlackBrixComments
 * @copyright Copyright (c)2020-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

"use strict";

if (typeof blackbrix == "undefined")
{
    var blackbrix = {};
}

if (typeof blackbrix.Comments == "undefined")
{
    blackbrix.Comments = {};
}

if (typeof blackbrix.Comments.AdminComments == "undefined")
{
    blackbrix.Comments.AdminComments = {};
}

blackbrix.Comments.AdminComments.onReset = function (event)
{
    event.preventDefault();

    document.getElementById("filter_body").value         = "";
    document.getElementById("filter_asset_title").value  = "";
    document.getElementById("filter_filter_email").value = "";
    document.getElementById("filter_ip").value           = "";
    document.getElementById("filter_asset_id").value     = "";
    document.getElementById("filter_published").value    = "";

    blackbrix.System.submitForm("browse", document.getElementById("adminForm"));

    return false;
}

blackbrix.System.documentReady(function ()
{
    blackbrix.System.addEventListener("comCommentsResetFilters", "click", blackbrix.Comments.AdminComments.onReset);

});