function lukeize() {
	var statusStrings = [
		["Open",				"Alright, stand back you pedestrians, this ain't no automobile accident."],
		["In Progress", 		"Any man playing grabass or fightin' in the building spends a night in the box."],
		["Closed", 				"You crazy handful of nothin'"],
		["Build Broken", 		"He's a natural born world-shaker."],
		["Done",				"Yeah, well, sometimes nothin' can be a real cool hand."],
		["Scheduled",			"Stop feedin' off me. Get out of here. I can't breathe. Give me some air."],
		["Cancelled",			"Look Cap'n, look what he done to Blue. He's dead, he's dead. he run himself plum to death."],
		["Rescheduled",			"What we've got here is... failure to communicate."],
		["Postponed",			"Nobody ever eat fifty eggs."],
		["Dev: Peer Review",	"I'm just standin' in the rain talkin' to myself"],
		["Dev",					"You know, them chains ain't medals."],
		["QA",					"Any man playing grabass or fightin' in the building spends a night in the box."],
		["Design",				"Yeah. I guess I gotta find my own way."]
	];
	var statuses = document.querySelectorAll(".jira-issue-status-lozenge");
	for (i=0; i<statuses.length; i++) {
		var str = statuses[i].innerHTML;
		for (j=0; j<statusStrings.length; j++) {
			if (str == statusStrings[j][0]) {
				statuses[i].innerHTML = statusStrings[j][1];
			}
		}
	}
}
lukeize();
setInterval(lukeize, 2000);