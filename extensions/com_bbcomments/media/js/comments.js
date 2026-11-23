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

blackbrix.Comments.getElementFromEvent = function (e)
{
    var clickedElement = null;

    if (typeof e.target === "object")
    {
        clickedElement = e.target;
    }
    else if (typeof e.srcElement === "object")
    {
        clickedElement = e.srcElement;
    }

    return clickedElement;
};

blackbrix.Comments.getAssetIdFromEvent = function (e)
{
    var clickedElement = blackbrix.Comments.getElementFromEvent(e);

    if (clickedElement === null)
    {
        return 0;
    }

    return clickedElement.dataset["bbcommentsid"] ?? 0;
};

blackbrix.Comments.onEditButton = function (e)
{
    e.preventDefault();

    /**
     * Construct the edit URL for the comment. The blackbrix.Comments.editURL script option key comes from the
     * components/com_bbcomments\View\Comments\Html.php file.
     */
    var id = blackbrix.Comments.getAssetIdFromEvent(e);

    window.location = atob(Joomla.getOptions("blackbrix.Comments.editURL")).replace("__ID__", id);
};

blackbrix.Comments.onDeleteButton = function (e)
{
    e.preventDefault();

    var shouldProceed = confirm(Joomla.Text._("COM_BBCOMMENTS_COMMENTS_DELETE_PROMPT"));

    if (!shouldProceed)
    {
        return;
    }

    /**
     * Construct the edit URL for the comment. The blackbrix.Comments.editURL script option key comes from the
     * components/com_bbcomments\View\Comments\Html.php file.
     */
    var id = blackbrix.Comments.getAssetIdFromEvent(e);

    window.location = atob(Joomla.getOptions("blackbrix.Comments.deleteURL")).replace("__ID__", id);
};

blackbrix.Comments.onPublishButton = function (e)
{
    e.preventDefault();

    /**
     * Construct the edit URL for the comment. The blackbrix.Comments.publishURL script option key comes from the
     * components/com_bbcomments\View\Comments\Html.php file.
     */
    var id = blackbrix.Comments.getAssetIdFromEvent(e);

    window.location = atob(Joomla.getOptions("blackbrix.Comments.publishURL")).replace("__ID__", id);
};

blackbrix.Comments.onUnpublishButton = function (e)
{
    e.preventDefault();

    /**
     * Construct the edit URL for the comment. The blackbrix.Comments.unpublishURL script option key comes from
     * the components/com_bbcomments\View\Comments\Html.php file.
     */
    var id = blackbrix.Comments.getAssetIdFromEvent(e);

    window.location = atob(Joomla.getOptions("blackbrix.Comments.unpublishURL")).replace("__ID__", id);
};

blackbrix.Comments.onMarkHamButton = function (e)
{
    e.preventDefault();

    var id = blackbrix.Comments.getAssetIdFromEvent(e);

    window.location = atob(Joomla.getOptions("blackbrix.Comments.markhamURL")).replace("__ID__", id);
};

blackbrix.Comments.onMarkSpamButton = function (e)
{
    e.preventDefault();

    var id = blackbrix.Comments.getAssetIdFromEvent(e);

    window.location = atob(Joomla.getOptions("blackbrix.Comments.markspamURL")).replace("__ID__", id);
};

blackbrix.Comments.onMarkPossibleSpamButton = function (e)
{
    e.preventDefault();

    var id = blackbrix.Comments.getAssetIdFromEvent(e);

    window.location = atob(Joomla.getOptions("blackbrix.Comments.possiblespamURL")).replace("__ID__", id);
};

blackbrix.Comments.onReplyButton = function (e)
{
    e.preventDefault();

    var clickedElement = blackbrix.Comments.getElementFromEvent(e);

    if (clickedElement === null)
    {
        return false;
    }

    blackbrix.Comments.unhideReplyArea();

    var parentId      = (clickedElement.dataset["bbcommentsid"] ?? 0) * 1;
    var inReplyToName = clickedElement.dataset["bbcommentsreplyto"] ?? 0;
    var form          = document.forms["bbcommentsCommentForm"];
    var wrapper       = document.getElementById("bbcomments-comment-inreplyto-wrapper");

    form["jform[parent_id]"].value = parentId;
    wrapper.classList.add('d-none');
    wrapper.style.display = "none";

    if (parentId !== 0)
    {
        var inReplyTo       = document.getElementById("bbcomments-comment-inreplyto-name");
        inReplyTo.innerText = inReplyToName;

        wrapper.classList.remove('d-none');
        wrapper.style.display = "";
    }

    document.location.hash = "";
    document.location.hash = "#bbcommentsCommentForm";
};

blackbrix.Comments.onCancelReplyButton = function (e)
{
    e.preventDefault();

    var form      = document.forms["bbcommentsCommentForm"];
    var wrapper   = document.getElementById("bbcomments-comment-inreplyto-wrapper");
    var inReplyTo = document.getElementById("bbcomments-comment-inreplyto-name");

    form["jform[parent_id]"].value = 0;
    wrapper.style.display          = "none";
    wrapper.classList.add('d-none');
    inReplyTo.innerText            = "";
};

blackbrix.Comments.saveCommenterInfo = function (e)
{
    if (typeof window.localStorage === "undefined")
    {
        return;
    }

    var storedName  = window.localStorage.getItem("bbcommentsGuestComment_name");
    var storedEmail = window.localStorage.getItem("bbcommentsGuestComment_email");

    window.localStorage.removeItem("bbcommentsGuestComment_name");
    window.localStorage.removeItem("bbcommentsGuestComment_email");

    var elSaveInfo = document.getElementById("bbcomments-comment-form-saveinfo");

    if (!(elSaveInfo instanceof Element) || !elSaveInfo.checked)
    {
        return;
    }

    var elName  = document.getElementById("bbcomments-comment-form-name");
    var elEmail = document.getElementById("bbcomments-comment-form-email");

    if (!(elName instanceof Element) || !(elEmail instanceof Element))
    {
        return;
    }

    var myName  = elName.value;
    var myEmail = elEmail.value;

    if ((storedName === null) || (storedEmail === null))
    {
        storedName  = myName;
        storedEmail = myEmail;
    }

    if ((myName !== storedName) || (myEmail !== storedEmail) || (myName === "") || (myEmail === ""))
    {
        window.localStorage.removeItem("bbcommentsGuestComment_name");
        window.localStorage.removeItem("bbcommentsGuestComment_email");

        return;
    }

    window.localStorage.setItem("bbcommentsGuestComment_name", myName);
    window.localStorage.setItem("bbcommentsGuestComment_email", myEmail);
};

blackbrix.Comments.loadCommenterInfo = function ()
{
    if (typeof window.localStorage === "undefined")
    {
        return;
    }

    var storedName  = window.localStorage.getItem("bbcommentsGuestComment_name");
    var storedEmail = window.localStorage.getItem("bbcommentsGuestComment_email");

    if ((storedName === "") || (storedEmail === "") || (storedName === null) || (storedEmail === null))
    {
        storedName  = "";
        storedEmail = "";
    }

    var elName  = document.getElementById("bbcomments-comment-form-name");
    var elEmail = document.getElementById("bbcomments-comment-form-email");

    if (!(elName instanceof Element) || !(elEmail instanceof Element))
    {
        return;
    }

    if ((elName.value == "") && (elEmail.value == ""))
    {
        elName.value  = storedName;
        elEmail.value = storedEmail;
    }

    if ((storedName === "") || (storedEmail === ""))
    {
        return;
    }

    var elSaveInfo = document.getElementById("bbcomments-comment-form-saveinfo");

    if (elSaveInfo instanceof Element)
    {
        elSaveInfo.setAttribute("checked", "checked");
    }
};

blackbrix.Comments.onHiderButton = function (e)
{
    blackbrix.Comments.unhideReplyArea();
};

blackbrix.Comments.unhideReplyArea = function ()
{
    var elFormArea = document.getElementById("bbcommentsCommentForm");
    var elHider    = document.getElementById("bbcomments-comment-hider");

    elFormArea.classList.remove('d-none');
    elFormArea.style.display = "";

    if (elHider)
    {
        elHider.classList.add('d-none');
        elHider.style.display = "none";
    }
};

blackbrix.Comments.onSubmit = function (e) {
    const elButton = blackbrix.Comments.getElementFromEvent(e);

    if (!elButton)
    {
        return;
    }

    const elForm = document.forms['bbcomments-comment-form'];

    if (!elForm)
    {
        return;
    }

    e.preventDefault();

    elButton.classList.add('disabled');
    elButton.style.disabled = "disabled";
    elButton.innerHTML      = Joomla.Text._("COM_BBCOMMENTS_COMMENTS_FORM_BTN_SUBMIT_PLEASE_WAIT");

    // The small delay is necessary for the browser to have the chance to redraw the button
    window.setTimeout(() => elForm.submit(), 150);
};

[].slice.call(document.querySelectorAll("button.bbcomments-comment-edit-btn")).forEach(function (el)
{
    el.addEventListener("click", blackbrix.Comments.onEditButton);
});

[].slice.call(document.querySelectorAll("button.bbcomments-comment-delete-btn")).forEach(function (el)
{
    el.addEventListener("click", blackbrix.Comments.onDeleteButton);
});

[].slice.call(document.querySelectorAll("button.bbcomments-comment-reply-btn")).forEach(function (el)
{
    el.addEventListener("click", blackbrix.Comments.onReplyButton);
});

[].slice.call(document.querySelectorAll("button.bbcomments-comment-unpublish-btn")).forEach(function (el)
{
    el.addEventListener("click", blackbrix.Comments.onUnpublishButton);
});

[].slice.call(document.querySelectorAll("button.bbcomments-comment-publish-btn")).forEach(function (el)
{
    el.addEventListener("click", blackbrix.Comments.onPublishButton);
});

[].slice.call(document.querySelectorAll("button.bbcomments-comment-markham-btn")).forEach(function (el)
{
    el.addEventListener("click", blackbrix.Comments.onMarkHamButton);
});

[].slice.call(document.querySelectorAll("button.bbcomments-comment-markspam-btn")).forEach(function (el)
{
    el.addEventListener("click", blackbrix.Comments.onMarkSpamButton);
});

[].slice.call(document.querySelectorAll("button.bbcomments-comment-possiblespam-btn")).forEach(function (el)
{
    el.addEventListener("click", blackbrix.Comments.onMarkPossibleSpamButton);
});

var inReplyTo = document.getElementById("bbcomments-comment-inreplyto-cancel");
if (inReplyTo)
{
    inReplyTo.addEventListener("click", blackbrix.Comments.onCancelReplyButton);
}

var commentForm = document.getElementById("bbcommentsCommentForm");
if (commentForm)
{
    commentForm.addEventListener("submit", blackbrix.Comments.saveCommenterInfo);
}

var elHider = document.getElementById("bbcomments-comment-hider-button");
if (elHider)
{
    elHider.addEventListener("click", blackbrix.Comments.onHiderButton);
}

if (Joomla.getOptions("blackbrix.Comments.pleaseWait", 0) == 1)
{
    [].slice.call(document.querySelectorAll("button.bbcomments-comment-submit-btn")).forEach(function (el)
    {
        el.addEventListener("click", blackbrix.Comments.onSubmit);
    });
}

blackbrix.Comments.loadCommenterInfo();

// Native anchors and guestbook buttons. Safe with hidden or already visible forms.
(function () {
  function openForm(event) {
    if (window.blackbrix && blackbrix.Comments && typeof blackbrix.Comments.unhideReplyArea === 'function') {
      blackbrix.Comments.unhideReplyArea();
      var form = document.getElementById('bbcommentsCommentForm');
      if (form && event) {
        event.preventDefault();
        form.scrollIntoView({behavior: 'smooth', block: 'start'});
      }
    }
  }
  if (window.location.hash === '#bbcomments-comments-section' || window.location.hash === '#bbcommentsCommentForm') {
    openForm(null);
  }
  document.addEventListener('click', function (event) {
    var trigger = event.target.closest('[data-bbcomments-open-form]');
    if (trigger) openForm(event);
  });
})();
