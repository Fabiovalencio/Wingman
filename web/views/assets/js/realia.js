jQuery(function($) {
    function InitCarousel() {
        if ($('#main .carousel .content ul').length !== 0) {
            $('#main .carousel .content ul').carouFredSel({
                scroll: {
                    items: 1
                },
                auto: false,
                items: {
                  minimum: 1,
                  width: 145
                },
                next: {
                    button: '#main .carousel-next',
                    key: 'right'
                },
                prev: {
                    button: '#main .carousel-prev',
                    key: 'left'
                }
            });
        }

        if ($('.widget .carousel .content ul').length !== 0) {
            $('.widget .carousel .content ul').carouFredSel({
                scroll: {
                    items: 1
                },
                auto: false,
                items: {
                    minimum: 1
                },
                next: {
                    button: function() {
                        return $(this).parent().siblings('.carousel-next');  
                    },
                    key: 'right'
                },
                prev: {
                    button: function() {
                        return $(this).parent().siblings('.carousel-prev');
                    },
                    key: 'left'
                }
            });
        }
    }

    InitCarousel();
});


jQuery(document).ready(function($) {

    InitPropertyCarousel();
    InitOffCanvasNavigation();
	InitChosen();
	InitEzmark();
	InitImageSlider();
	InitAccordion();
    InitPalette();

    function InitPalette() {
        if ($.cookie('palette') == 'true') {
            $('.palette').addClass('open');
        }

        $('.palette .toggle a').on({
            click: function(e) {
                e.preventDefault();

                var palette = $(this).closest('.palette');

                if (palette.hasClass('open')) {
                    palette.animate({'left': '-195'}, 500, function() {
                        palette.removeClass('open');
                    });
                    $.cookie('palette', false)
                } else {
                    palette.animate({'left': '0'}, 500, function() {
                        palette.addClass('open');
                        $.cookie('palette', true);
                    });
                }
            }
        });

        if ($.cookie('color-variant')) {
            var link = $('.palette').find('a.'+ $.cookie('color-variant'));
            var file = link.attr('href');
            var klass = link.attr('class');

            $.each(classList($('body')), function() {
                if (this.substring(0, 'color-'.length) == 'color-') {
                    $('body').removeClass('' + this + '');
                }
            });

            $('#color-variant').attr('href', file);
            $('body').addClass('color-' + klass);
        }

        if ($.cookie('pattern')) {
            $.each(classList($('body')), function() {
                if (this.substring(0, 'pattern-'.length) == 'pattern-') {
                    $('body').removeClass('' + this + '');
                }
            });

            $('body').addClass($.cookie('pattern'));
        }

        if ($.cookie('header')) {
            $.each(classList($('body')), function() {
                if (this.substring(0, 'header-'.length) == 'header-') {
                    $('body').removeClass('' + this + '');
                }
            });

            $('body').addClass($.cookie('header'));
        }

        if ($.cookie('layout')) {
            var layout = $.cookie('layout').split('-')[1];

            $.each(classList($('body')), function() {
                if (this.substring(0, 'layout-'.length) == 'layout-') {
                    $('body').removeClass('' + this + '');
                }
            });

            $('.layouts input[type=radio]').each(function() {
                if ($(this).attr('value') == layout) {
                    $(this).attr('checked', 'checked');
                } else {
                    $(this).attr('checked', null);
                }
            });
            $('input[type="radio"]').ezMark();
            $('body').addClass($.cookie('layout'));
        }

        $('.palette a').on({
            click: function(e) {
                e.preventDefault();

                // Colors
                if ($(this).closest('div').hasClass('colors')) {
                    var file = $(this).attr('href');
                    var klass = $(this).attr('class');
                    $('#color-variant').attr('href', file);

                    if (!$('body').hasClass(klass)) {
                        $.each(classList($('body')), function() {
                            if (this.substring(0, 'color-'.length) == 'color-') {
                                $('body').removeClass('' + this + '');
                            }
                        });

                        $('body').removeClass($.cookie('color-variant'));
                        $('body').addClass('color-' + klass);
                    }
                    $.cookie('color-variant', klass)
                }
                // Patterns
                else if ($(this).closest('div').hasClass('patterns')) {
                    var klass = $(this).attr('class');

                    if (!$('body').hasClass(klass)) {
                        $('body').removeClass($.cookie('pattern'));
                        $('body').addClass(klass);
                        $.cookie('pattern', klass);
                    }
                }
                // Headers
                else if ($(this).closest('div').hasClass('headers')) {
                    var klass = $(this).attr('class');

                    if (!$('body').hasClass(klass)) {
                        $('body').removeClass($.cookie('header'));
                        $('body').addClass(klass);
                        $.cookie('header', klass);
                    }
                }
            }
        });

        // Layouts
        $('.layouts input[type=radio]').on({
            click: function(event) {
                var klass = 'layout-' + $(this).attr('value');
                if (!$('body').hasClass(klass)) {
                    $('body').removeClass($.cookie('layout'));
                    $('body').addClass(klass);
                    $.cookie('layout', klass);
                }
            }
        });

        $('.palette .reset').on({
            click: function() {
                $('body').removeClass('color-' + $.cookie('color-variant'));
                $('#color-variant').attr('href', null);
                $.removeCookie('color-variant');

                $('body').removeClass($.cookie('pattern'));
                $.removeCookie('pattern');

                $('body').removeClass($.cookie('header'));
                $.removeCookie('header');

                $('body').removeClass($.cookie('layout'));
                $.removeCookie('layout');
            }
        })
    }

    function InitPropertyCarousel() {
        $("a.gallery").colorbox({ rel: "gallery"});

        $('.carousel.property .content li').on({
            click: function(e) {
                var selector = "#slide-" + $(this).data('index');

                $('.carousel.property .content li').each(function() {
                    $(this).removeClass('active');
                });
                $(this).addClass('active');

                $('.carousel.property .preview a').each(function() {
                    $(this).removeClass('active');
                });
                $(selector).addClass('active');


            }
        })
    }

    function InitImageSlider() {
        $('.iosSlider').iosSlider({
            desktopClickDrag: true,
            snapToChildren: true,
            infiniteSlider: true,
            navSlideSelector: '.slider .navigation li',
            onSlideComplete: function(args) {
                if(!args.slideChanged) return false;

                $(args.sliderObject).find('.slider-info').attr('style', '');

                $(args.currentSlideObject).find('.slider-info').animate({
                    left: '15px',
                    opacity: '.9'
                }, 'easeOutQuint');
            },
            onSliderLoaded: function(args) {
                $(args.sliderObject).find('.slider-info').attr('style', '');

                $(args.currentSlideObject).find('.slider-info').animate({
                    left: '15px',
                    opacity: '.9'
                }, 'easeOutQuint');
            },
            onSlideChange: function(args) {
                $('.slider .navigation li').removeClass('active');
                $('.slider .navigation li:eq(' + (args.currentSlideNumber - 1) + ')').addClass('active');
            },
            autoSlide: true,
            scrollbar: true,
            scrollbarContainer: '.sliderContainer .scrollbarContainer',
            scrollbarMargin: '0',
            scrollbarBorderRadius: '0',
            keyboardControls: true
        });
    }

    function InitAccordion() {
        $('.accordion').on('show', function (e) {
            $(e.target).prev('.accordion-heading').find('.accordion-toggle').addClass('active');
        });

        $('.accordion').on('hide', function (e) {
            $(this).find('.accordion-toggle').not($(e.target)).removeClass('active');
        });
    }

    function InitEzmark() {
        $('input[type="checkbox"]').ezMark();
        $('input[type="radio"]').ezMark();
    }

    function InitChosen() {
        $('select').each(function(index) {
            $(this).chosen({
                disable_search_threshold: 20
            });
        });
    }

    function InitOffCanvasNavigation() {
        $('#btn-nav').on({
            click: function() {
                $('body').toggleClass('nav-open');
            }
        })
    }

    function classList(elem){
        var classList = elem.attr('class').split(/\s+/);
        var classes = new Array(classList.length);

        if (classList !== undefined) {
            $.each(classList, function(index, item){
                classes[index] = item;
            });
        }
        return classes;
    }
});