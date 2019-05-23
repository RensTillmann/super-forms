# Email Reminders Add-on

### Download link:

Coming soon...


### Description

Send email reminders before and after a specific date (either form submission date or user defined date through [{tags}](tags-system)).<br />This is useful whenever you want to send emails like:
- A simple apointment or event reminder e.g: **Don't forget your apointment with your dog!**
- Asking customers about their experience e.g: **How was your trip?**
- Ask customers to leave a review e.g: **Please rate our product!**


### Features

- Configure unlimited amount of reminders per form
- Send reminder based on form submission date or based on user defined date through `{date;timestamp}` tag (consult [Tags System](tags-system) for more information about {tag} usage)
- Define how many days **before** or **after** the base date the reminder should be send example:<br />
  `0` = The same day, `1` = Next day, `5` = Five days after, `-1` = One day before, `-3` = Three days before
- Send reminder at a fixed time, or by offset 
	- **Fixed** (e.g: always at 09:00)
		- *Define at what time the reminder should be send (Use 24h format e.g: 13:00, 09:30)*
	- **Offset** (e.g: 2 hours after date)
		- *Define at what offset the reminder should be send based of the base time example:*<br />
  		  `2` = Two hours after, `-5` = Five hours before, `0.5` = 30 minutes before