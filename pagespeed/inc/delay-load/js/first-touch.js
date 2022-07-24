window.onload = function () {
    if (window.jQuery) {
        jQuery('body').addClass('goso-ready-js');
    }
}
var menuhbh_mobile = document.querySelector(".button-menu-mobile");
if (menuhbh_mobile !== null) {
    menuhbh_mobile.addEventListener('click', function () {
        if (document.body.classList.contains('goso-ready-js')) {
            return false;
        } else {
			if( menuhbh_mobile.classList.contains('header-builder') ){
				document.body.classList.toggle("open-mobile-builder-sidebar-nav");
			} else {
				document.body.classList.toggle("open-sidebar-nav");
			}
		}
    });
}
var menuhbh_toggle = document.querySelector(".goso-menuhbg-toggle");
if (menuhbh_toggle !== null) {
    menuhbh_toggle.addEventListener('click', function () {
        if (document.body.classList.contains('goso-ready-js')) {
            return false;
        }
        this.classList.toggle('active');
        document.body.classList.toggle("goso-menuhbg-open");
    });
}
var menuhbh_search = document.querySelector(".pcheader-icon a.search-click");
if (menuhbh_search !== null) {
    menuhbh_search.addEventListener('click', function (e) {
        if (document.body.classList.contains('goso-ready-js')) {
            return false;
        }
        var closet = this.closest('.wrapper-boxed'),
            pbcloset = this.closest('.goso_nav_col'),
            sform = document.querySelector('.show-search');

        if (closet.classList.contains('header-search-style-showup')) {
            this.classList.toggle('active');
            sform.classList.toggle('active');
        } else {
            this.classList.toggle('fade');
            sform.classList.toggle('fade');
        }

        var opentimeout = setTimeout(function () {
            closet.querySelector('.search-input').focus();
            if (pbcloset !== null && !!pbcloset.querySelector('.search-input')) {
                pbcloset.querySelector('.search-input').focus();
            }
        }, 200, function () {
            clearTimeout(opentimeout);
        });

        e.stopPropagation();
        return false;
    });
}
