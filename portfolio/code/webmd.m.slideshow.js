/* $Id$ */

//
// @package webmd.m.slideshow
//
// Creates a rotating slideshow (also called a carousel, rotator, etc.). Requires an
// HTML structure which looks like:
// 
//		<div class="slides">
//			<div class="slide">slide 1</div>
//			<div class="slide">slide 2</div>
//			<div class="slide">slide 3</div>
//		</div>
//
// This abstract object should be used as a basis for child slideshow objects
// created with webmd.object. Instantiate like this:
// 
//		$('div.slides').each(function() {
//			var ss = webmd.object(webmd.m.slideshow, {
//				container	: this
//				override1	: 'default override 1'
//				override2	: 'default override 2'
//			});
//			ss.init();
//		});
//
// Most of the functions can be chained like this:
// 
//		ss.init().trans('+1').metricsAdRefresh();
//
// To extend a parent object function without destroying it, create your object
// using the method described in the object-oriented development pattern documentation 
// {@link http://webservices.webmd.net/wswiki/Javascript-object-oriented-development-pattern.ashx#Overriding_a_parent_function_6}:
//
//		$('div.slides').each(function() {
//			var ss = webmd.object(webmd.m.slideshow, {
//				init : function() {
//					// call the parent init function using the child object as context
//					webmd.m.slideshow.init.call(this);
//
//					// additional child slideshow init setup goes here
//					alert('my child object is set up');
//				}
//			});
//			ss.init();
//		});
//
// Notes:
//
// * When styling your slideshow, you should assign the CSS position: relative
//   attribute to the parent container. Slides are absolutely positioned within
//   the relative container by the effect plugin (hslider, fade, etc).
// * Elements other than slides (i.e. nav, slide count, etc.) should NOT be placed
//   within the slides container.
//
webmd.m.slideshow = {
	
	
	//
	// Defaults
	//
	// @param bool			auto			Tell the slideshow to automatically play
	// @param int			autoDelay		Delay in milliseconds before autoplay starts
	// @param function		autoFunction	Code for auto transition. Defaults to a 1 slide increment.
	// @param int			autoIncrement	Time in milliseconds between automatic slide transitions
	// @param int			autoLoops		Specifying a number higher than zero will stop autoplay after x number of loops
	// @param bool			circular		If true, slideshow will loop
	// @param string|object container		Accepts a jQuery selector or a DOM element
	// @param function		dBuild			Pass a function here to do things before slides are cached internally
	// @param function		dSlides			Pass a function here to do things after slides are cached internally
	// @param string		effect			Tells the slideshow which effect to use. Core effects are 'fade' or 'hslider'
	// @param mixed			effectParam		A parameter passed to the effect, usally an integer for milliseconds for now
	// @param function		fAfter			Pass a function here to run after a slide transition finishes. 'self' arg is the slideshow object.
	// @param function		fBefore			Pass a function here to run before a slide transition starts. 'self' arg is the slideshow object.
	// @param int|string	height			Specify a height for the slideshow as an integer or as string 'dynamic'
	// @param string		slideContainer	Accepts a jQuery selection for slides within the parent DOM element
	// @param int			startAt			Pass an integer to start at a particular slide (not zero-based, i.e. pass 2 to start at 2)
	// @param int|string	width			Specify a width for the slideshow as an integer or as string 'dynamic'
	//
	auto			: false,
	autoDelay		: 1,
	autoFunction	: function(self) { self.trans('+1'); },
	autoIncrement 	: 2000,
	autoLoops		: 0,
	circular		: false,
	container		: '#slides',
	dBuild			: function() { return this; },
	dSlides			: function() { return this; },
	effect			: 'fade',
	effectParam		: 500,
	fBefore			: function(self) { return self; },
	fAfter			: function(self) { return self; },
	height			: 0,
	slideContainer	: '.slide',
	startAt			: 1,
	width			: 0,
	
	
	//
	// webmd.m.slideshow.init
	//
	// Sets up the slideshow.
	//
	// @return object the slideshow object for chainability
	//
	init : function() {
		this.dBuild()._cacheContainer()._calcWidthHeight().dSlides().firstSlide();
		if (this.auto) { this.autoStart(); }
		return this;
	},
	
	//
	// webmd.m.slideshow.autoStart
	//
	// Starts slideshow autoplay. Loops infinitely if the autoLoops param is set to 0.
	//
	// @return object the slideshow object for chainability
	//
	autoStart : function() {
		var self = this;
		this._autoTimeout = setTimeout(function() {
			self._autoInterval = setInterval(function() {
				if (self.autoLoops) {
					if (!self._autoLoopCount) { self._autoLoopCount = 1; }
					if (self.autoLoops <= self._autoLoopCount) { self.autoStop(); }
					self._autoLoopCount++;
				}
				if (self.autoFunction) { self.autoFunction(self); }
			}, self.autoIncrement );
		}, this.autoDelay );
		return this;
	},
	
	
	//
	// webmd.m.slideshow.autoStop
	// 
	// Stops automatic play set by webmd.m.slideshow.autoStop.
	//
	// @return object the slideshow object for chainability
	//
	autoStop : function() {
		if (this._autoInterval) { self._autoLoopCount = 0; clearInterval(this._autoInterval); }
		return this;
	},

	
	//
	// webmd.m.slideshow.firstSlide
	//
	// Sets and shows the start slide. You can override the logic
	// to start at a particular slide based on a URL hash, for example:
	// 
	//		Where the URL is http://www.webmd.com/slideshow#7
	//
	//		var ss = webmd.object(webmd.m.slideshow, {
	//			firstSlide : function() {
	//				var hash = webmd.url.getHash();
	//				if (hash) { this.startAt = hash; }
	//				webmd.m.slideshow.firstSlide.call(this);
	//			}
	//		});
	//		ss.init();
	//
	//		...would start the slideshow on slide 7
	//
	// @return object the slideshow object for chainability
	//
	firstSlide : function() {
		this.current = this.startAt || 1;
		if(!this.restart) { this.restart = (this.circular) ? true : false; }
		this.trans(this.current);
		return this;
	},
	
	
	//
	// webmd.m.slideshow.metricsAdRefresh
	//
	// Wrapper for webmd.ads.refresh.
	//
	// @return object the slideshow object for chainability
	//
	metricsAdRefresh : function() {
		try { webmd.ads.refresh(); } catch(e) {}
		return this;
	},
	
	
	//
	// webmd.m.slideshow.metricsAjax
	//
	// Omniture pageview.
	//
	// @return object the slideshow object for chainability
	//
	metricsPV : function() {
		try { wmdPageview(window.location.href); } catch(e) {}
		return this;
	},
	
	
	//
	// webmd.m.slideshow.metricsAjax
	//
	// Makes AJAX call to XML file to keep Comscore happy.
	//
	// @return object the slideshow object for chainability
	// 
	metricsXML : function() {
		try {
			var today=new Date();
			var h=String(today.getHours());
			var m=String(today.getMinutes());
			var s=String(today.getSeconds());
			var timeStamp=h+m+s;
			$.get('/api/proxy/proxy.aspx?url=http://img.webmd.com/slideshow_fp/slideshow_fp.wxml?date=' + timeStamp);
		} catch(e) {}
		return this;
	},
	
	
	//
	// webmd.m.slideshow.trans
	//
	// Tells the slideshow to increment forward or backwards.
	//
	// @param string n in this format: '+1' or '-2'
	// @todo needs to be rewritten to account for webmd.m.slideshow.effects being deprecated
	// @return object the slideshow object for chainability
	//
	trans : function(n) {
		this.transCommand = n; // save the command
		var self = this;
		n = (!n) ? "+1" : n;
		self.n = (typeof n == "number") ? (n-1) : (n == 'f') ? 0 : (n == 'l') ? self.slides.length-1 : (n == 'p') ? (self.current-1) : (self.current + Number(n));
		self.n = (self.n < 0 && self.circular) ? (self.slides.length-1) : (self.n < 0) ? self.current : (self.n >= 0 && self.n < self.slides.length) ? self.n : (self.current + 1);
		
		// only do a transition when another effect isn't in progress
		if(!self.effectInProgress) {
			
			// redirect at end of slideshow or restart otherwise do nothing
			if(self.n == self.slides.length) {
				if(self.lastSlide) { self.lastSlide(self); }
				if(self.dir) { document.location = self.dir; }
				if(self.restart) { self.circular = true; self.n = 0; }
			}
			
			if(self.n != self.current && self.n >= 0 && self.n < self.slides.length) {
				self.effectInProgress = true;
				
				// if a "before" function exists, call now (call to "after" function is written into _effectDone)
				if(self.fBefore) { self.fBefore(self); }		
				
				// if effect is specified and exists, pass to effect function
				if(self.effect && self.effects[self.effect]) { self.effects[self.effect](self.n, self, self.effectParam); }
				
				// if no effect specified default to fade
				else { self.effects["fade"](self.n, self); }
				
				// set current slide
				self.current = self.n;
			}
		}
		return this;
	},
	
	
	//
	// webmd.m.slideshow.effectFade
	//
	// Fader effect for transitions. Call by webmd.m.slideshow.trans, should not be called directly. Not chainable.
	//
	// @param int n			slide number
	// @param int duration	length of effect in milliseconds
	//	
	effectFade : function(n, duration) {
			
		var self = this; // @todo can probably change references back to 'this'
		
		var current = (self.current >= self.slides.length) ? 0 : self.current || 0;
		var duration = (duration) ? duration : self.effectParam;
		
		// do initial build work on first call
		if(!self.effectBuilt) {
			
			// set up the container
			self.$container.css({
				height : self.height,
				overflow : 'hidden',
				position : 'relative',
				width : self.width
			});
			
			// position and hide all slides
			$(self.slides).css({
				display : 'none',
				left : 0,
				position : 'absolute',
				top : 0
			});
			self.effectBuilt = true;
		}
		
		// fade out current, start fade in of next when done
		if(self.slides[current]) { $(self.slides[current]).fadeOut(duration); }
		
		// fade in next slide, call effectDone function after load
		$(self.slides[n]).fadeIn(duration, function() { self._effectDone(); });
		
		// animate to dynamic height/width
		if (this._dynamicHeight || this._dynamicWidth) {
			var containerAnimationParams = {};
			var $next = $(this.slides[n]);
			if (this._dynamicHeight) { containerAnimationParams.height = $next.height(); }
			if (this._dynamicWidth) { containerAnimationParams.width = $next.width(); }
			this.$container.animate(containerAnimationParams);
		}
		
		
	},
	
	
	
	//
	// webmd.m.slideshow.effectHslider
	//
	// Horizontal slider effect for transitions. Call by webmd.m.slideshow.trans, should not be called directly. Not chainable.
	//
	// @todo need to add multi-slide transition functionality
	// @todo need to add dynamic vertical resize functionality
	// @param int n			slide number
	// @param int duration	length of effect in milliseconds
	//	
	effectHslider : function(n, duration) {
		
		var self = this;  // @todo can probably change references back to 'this'
		
		var current = (self.current >= self.slides.length) ? 0 : self.current || 0;
		var duration = (duration) ? duration : self.effectParam;
		var isFirstFrame = current === 0, isLastFrame = current == length;
		var isForward = self.transCommand ? (self.transCommand.toString().indexOf("+") > -1) : true;
		var toNext = Math.abs(self.transCommand);
		
		// do initial build work on first call
		if(!self.effectBuilt) {
			
			// set up the container
			self.$container.css({
				height : self.height,
				overflow : 'hidden',
				position : 'relative',
				width : self.width
			});
			
			// position and hide all slides
			$(self.slides).css({
				display : 'none',
				left : 0,
				position : 'absolute',
				top : 0
			});
			
			// show the first slide
			$(self.slides[n]).show();
			
			self.effectBuilt = true;
			self._effectDone();
			
		} else {
			// fade out current, start fade in of next when done
			$(self.slides[n]).css({
				display: 'block',
				left: (isForward ? self.width : self.width*-1)
			});
					
			// animate next and current slide
			$(self.slides[n]).animate({ left: 0 }, { duration : duration } );
			$(self.slides[current]).animate(
				{ left: (isForward ? self.width*-1 : self.width ) },
				{ duration : duration, complete : function() { self._effectDone(); } }
			);
		}
		
	},
	
	
	//
	// ======================================================================
	// Should be treated as private functions, i.e. not referenced externally
	// or overwritten/extended
	// ======================================================================
	//
	
	
	//
	// webmd.m.slideshow._cacheContainer
	//
	// Cache the container jQuery and slides. Runs once during init.
	//
	// @return object the slideshow object for chainability
	//
	_cacheContainer : function() {
		this.$container = typeof(this.container == 'string') ? $(this.container) : this.container;
		this.slides = this.$container.find( this.slideContainer );
		return this;	
	},
	
	
	//
	// webmd.m.slideshow._calcWidthHeight
	//
	// Various height/width setup. Runs once during init.
	//
	// @return object the slideshow object for chainability
	//
	_calcWidthHeight : function() {
		// if no height/width specified, get from first child
		var $fc = $(this.slides[0]);
		if (!this.height) { this.height = $fc.height(); }
		if (!this.width) { this.width = $fc.width(); }
		
		// if height is set to string 'dynamic', get height/width from startAt slide
		if (this.height == 'dynamic') {
			this._dynamicHeight = true;
			this.height = $(this.slides[this.startAt-1]).height();
		}
		if (this.width == 'dynamic') {
			this._dynamicWidth = true;
			this.width = $(this.slides[this.startAt-1]).width();
		}
		return this;
	},
	
	
	//
	// webmd.m.slideshow._effectDone
	//
	// Sets appropriate internal vars when an effect finishes.
	//
	// @return object the slideshow object for chainability 
	//
	_effectDone : function() {
		var self = this;
		if(self.fAfter) { self.fAfter(self); }		
		self.effectInProgress = false;
		return this;
	},
	
	
	//
	// ==========================================================================
	// Deprecated vars and functions
	//
	// These are still supported for now, but you shouldn't rely on them to exist
	// in the future when writing new child objects. You should also update your
	// existing child objects to stop using them.
	// ==========================================================================
	//
	
	
	//
	// webmd.m.slideshow.effects
	//
	// Stub container for core slideshow effects. Moved effects functions to top level
	// to make them extendable with webmd.object.
	//
	effects : {
				
		//
		// webmd.m.slideshow.effects.fade
		//
		// Stub function for backwards compatibility.
		//
		// @return object the slideshow object for chainability 
		//
		fade : function(n, self, duration) {
			return self.effectFade(n, duration);
		},
		
		
		//
		// webmd.m.slideshow.effects.hslider
		//
		// Stub function for backwards compatibility.
		//
		// @return object the slideshow object for chainability 
		//
		hslider : function(n, self, duration) {
			return self.effectHslider(n, duration);
		}
		
	},
	
	
	//
	// webmd.m.slideshow.metrics defaults
	//
	// @param bool	metricsAds		Tekks the slideshow to refresh ads
	// @param bool	metricsAjax		Tells the slideshow to call 
	// @param bool	metricsSkipPV	A relic from before the code was ported to webmd.object, probably not used anywhere
	// @param bool	metricsWmdTrack	Tells the slideshow to call the Omniture PV function after every transition
	//
	metricsAds		: true,
	metricsAjax		: true,
	metricsSkipPV	: true,
	metricsWmdTrack	: true,
	
	
	//
	// webmd.m.slideshow.metrics
	//
	// All the metrics calls used to happen within this function, but this has since
	// been broken into smaller functions and the config vars deprecated
	//
	// @return object the slideshow object for chainability 
	//
	metrics : function() {
		if (!this.metricsSkipPV) {
			if (this.metricsAds) { this.metricsAdRefresh(); }
			if (this.metricsAjax) { this.metricsXML(); }
			if (this.metricsWmdTrack) { this.metricsPV(); }
		} else {
			this.metricsSkipPV = false;
		}
		return this;
	},
	
	
	//
	// Misc defaults
	//
	// @param int	delayAfter		Was used in the old ss library for the audio slideshow, may need to be added back
	// @param int	delayBefore		Was used in the old ss library for the audio slideshow, may need to be added back
	//
	delayAfter		: 0,
	delayBefore		: 0
	
	
};