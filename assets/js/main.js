$(document).ready(function($) {

	"use strict";

	var loader = function() {
		
		setTimeout(function() { 
			if($('#pb_loader').length > 0) {
				$('#pb_loader').removeClass('show');
			}
		}, 700);
	};
	loader();

	// scroll
	var scrollWindow = function() {
		$(window).scroll(function(){
			var $w = $(this),
					st = $w.scrollTop(),
					navbar = $('.pb_navbar'),
					sd = $('.js-scroll-wrap');

			if (st > 150) {
				if ( !navbar.hasClass('scrolled') ) {
					navbar.addClass('scrolled');	
				}
			} 
			if (st < 150) {
				if ( navbar.hasClass('scrolled') ) {
					navbar.removeClass('scrolled sleep');
				}
			} 
			if ( st > 350 ) {
				if ( !navbar.hasClass('awake') ) {
					navbar.addClass('awake');	
				}
				
				if(sd.length > 0) {
					sd.addClass('sleep');
				}
			}
			if ( st < 350 ) {
				if ( navbar.hasClass('awake') ) {
					navbar.removeClass('awake');
					navbar.addClass('sleep');
				}
				if(sd.length > 0) {
					sd.removeClass('sleep');
				}
			}
		});
	};
	scrollWindow();
	
	// slick sliders
	var slickSliders = function() {
		$('.single-item').slick({
			slidesToShow: 1,
		  slidesToScroll: 1,
		  dots: true,
		  infinite: true,
		  autoplay: false,
	  	autoplaySpeed: 2000,
	  	nextArrow: '<span class="next"><i class="ion-ios-arrow-right"></i></span>',
	  	prevArrow: '<span class="prev"><i class="ion-ios-arrow-left"></i></span>',
	  	arrows: true,
	  	draggable: false,
	  	adaptiveHeight: true
		});

		$('.single-item-no-arrow').slick({
			slidesToShow: 1,
		  slidesToScroll: 1,
		  dots: true,
		  infinite: true,
		  autoplay: true,
	  	autoplaySpeed: 2000,
	  	nextArrow: '<span class="next"><i class="ion-ios-arrow-right"></i></span>',
	  	prevArrow: '<span class="prev"><i class="ion-ios-arrow-left"></i></span>',
	  	arrows: false,
	  	draggable: false
		});

		$('.multiple-items').slick({
		  slidesToShow: 3,
		  slidesToScroll: 1,
		  dots: true,
		  infinite: true,
		  
		  autoplay: true,
	  	autoplaySpeed: 2000,

		  arrows: true,
		  nextArrow: '<span class="next"><i class="ion-ios-arrow-right"></i></span>',
	  	prevArrow: '<span class="prev"><i class="ion-ios-arrow-left"></i></span>',
	  	draggable: false,
	  	responsive: [
		    {
		      breakpoint: 1125,
		      settings: {
		        slidesToShow: 2,
		        slidesToScroll: 1,
		        infinite: true,
		        dots: true
		      }
		    },
		    {
		      breakpoint: 900,
		      settings: {
		        slidesToShow: 2,
		        slidesToScroll: 2
		      }
		    },
		    {
		      breakpoint: 580,
		      settings: {
		        slidesToShow: 1,
		        slidesToScroll: 1
		      }
		    }
		  ]
		});

		$('.js-pb_slider_content').slick({
		  slidesToShow: 1,
		  slidesToScroll: 1,
		  arrows: false,
		  fade: true,
		  asNavFor: '.js-pb_slider_nav',
		  adaptiveHeight: false
		});
		$('.js-pb_slider_nav').slick({
		  slidesToShow: 3,
		  slidesToScroll: 1,
		  asNavFor: '.js-pb_slider_content',
		  dots: false,
		  centerMode: true,
		  centerPadding: "0px",
		  focusOnSelect: true,
		  arrows: false
		});

		$('.js-pb_slider_content2').slick({
		  slidesToShow: 1,
		  slidesToScroll: 1,
		  arrows: false,
		  fade: true,
		  asNavFor: '.js-pb_slider_nav2',
		  adaptiveHeight: false
		});
		$('.js-pb_slider_nav2').slick({
		  slidesToShow: 3,
		  slidesToScroll: 1,
		  asNavFor: '.js-pb_slider_content2',
		  dots: false,
		  centerMode: true,
		  centerPadding: "0px",
		  focusOnSelect: true,
		  arrows: false
		});
	};
	slickSliders();

	// navigation
	var OnePageNav = function() {
		$(".smoothscroll[href^='#'], #probootstrap-navbar ul li a[href^='#']").on('click', function(e) {
			e.preventDefault();
		 	var hash = this.hash,
			navToggler = $('.navbar-toggler');
		 	$('html, body').animate({

		    scrollTop: $(hash).offset().top
		  	}, 700, 'easeInOutExpo', function(){
		    	window.location.hash = hash;
		  	});

		  	if (navToggler.is(':visible') && !$(this).hasClass("no-nav-expand")) {
		  		navToggler.click();
		  	}
		});
	};
	OnePageNav();

	var offCanvasNav = function() {
		var toggleNav = $('.js-pb_nav-toggle'),
				offcanvasNav = $('.js-pb_offcanvas-nav_v1');
		if( toggleNav.length > 0 ) {
			toggleNav.click(function(e){
				$(this).toggleClass('active');
				offcanvasNav.addClass('active');
				e.preventDefault();
			});
		}
		offcanvasNav.click(function(e){
			if (offcanvasNav.hasClass('active')) {
				offcanvasNav.removeClass('active');
				toggleNav.removeClass('active');
			}
			e.preventDefault();
		})
	};
	offCanvasNav();

	var ytpPlayer = function() {
		if ($('.ytp_player').length > 0) { 
			$('.ytp_player').mb_YTPlayer();	
		}
	};
	ytpPlayer();
});

function timeRemainingFormatter(millis) {
	var time = {};

	var days = Math.floor(millis / (24*60*60*1000));
	time.days = days.toString().padStart(2, "0");
	millis -= days * 24 * 60 * 60 * 1000;

	var hours = Math.floor(millis / (60*60*1000));
	time.hours = hours.toString().padStart(2, "0");
	millis -= hours * 60 * 60 * 1000;

	var minutes = Math.floor(millis / (60*1000));
	time.minutes = minutes.toString().padStart(2, "0");
	millis -= minutes * 60 * 1000;

	var seconds = Math.floor(millis / 1000);
	time.seconds = seconds.toString().padStart(2, "0");

	return time;
}

function countdown(openDate, closeDate) {
	var currentDate = new Date();

	if (openDate - currentDate < 0) {
		openDate.setFullYear(openDate.getFullYear() + 1);
	}

	if (closeDate - currentDate < 0) {
		closeDate.setFullYear(closeDate.getFullYear() + 1);
	}

	var openDateDiff = openDate - currentDate;
	var closeDateDiff = closeDate - currentDate;

	var time, label;
	if (openDateDiff < closeDateDiff) {
		time = timeRemainingFormatter(openDateDiff);
		label = $("#countdown_label").attr("data-closed-text");
	} else {
		time = timeRemainingFormatter(closeDateDiff);
		label = $("#countdown_label").attr("data-open-text");
	}
	$("#countdown_label").text(label);
	$("#countdown_value").text("{0} days, {1} hours, {2} minutes, {3} seconds".formatUnicorn(time.days, time.hours, time.minutes, time.seconds));
}

function startCountdown() {
	String.prototype.formatUnicorn = String.prototype.formatUnicorn ||
		function () {
			"use strict";
			var str = this.toString();
			if (arguments.length) {
				var t = typeof arguments[0];
				var key;
				var args = ("string" === t || "number" === t) ?
					Array.prototype.slice.call(arguments)
					: arguments[0];

				for (key in args) {
					str = str.replace(new RegExp("\\{" + key + "\\}", "gi"), args[key]);
				}
			}

			return str;
		};

	var currentDate = new Date();
	var openDate = new Date("February 1, 0000 00:00:00 UTC-7:00");
	openDate.setFullYear(currentDate.getFullYear());
	var closeDate = new Date("April 1, 0000 00:00:00 UTC-7:00");
	closeDate.setFullYear(currentDate.getFullYear());

	setInterval(countdown, 900, openDate, closeDate);
}

startCountdown();
