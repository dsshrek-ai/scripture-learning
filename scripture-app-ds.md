# Scripture Learning App

## Purpose

- The purpose of this app is to facilitate the learning of scriptures.

## db Method (Leave the design and setup of the db to my other system). You can suggest fields.

- Scriptures are stored in MyWorldDb for use in memorization
  
  - Key ID
  
  - Reference
  
  - Book
  
  - Scripture Block
  
  - Date Reviewed
  
  - Date Memorized
  
  - Boolean memorized or not
  
  - Flag for use - if not active then the scripture is saved for later
  
  - Text Percent 
  
  - Reference Percent
  
  - Exact Wording

## - App Features

- Show list of scriptures not yet flagged for memorizaion

- Spaced Repetition

- Ability to add scriptures

- Scriptures keyed to user (a numeric field) so the app could be used by anyone. I doubt it will ever be more than 30 people.

- Full-text reading

- Progressing word hiding

- First-letter mode

- Recite from Reference

- Spaced-repitition review queue

- I have some references and scriptures ready. 

- I like the flow: Home → My Scriptures → Learn → Practice → Daily Review—and sketch out what each mobile screen should do.

## First Version

The first version should focus on the core learning loop.

Required MVP features:

1. User identification
2. Scripture library
3. Add scripture
4. Flag scripture for memorization
5. My Scriptures
6. Full-text reading
7. Progressive word hiding
8. Word-length blanks
9. First-letter mode
10. Recite from reference
11. Basic progress tracking
12. Memorized status
13. Spaced-repetition scheduling
14. Daily Review queue

Core Design Principle
=====================

The app should gradually remove assistance as the user learns.

The general progression is:

**See Everything**

→ **See Most Words**

→ **See Some Words**

→ **See Word Shapes / Lengths**

→ **See First Letters**

→ **See Minimal Hints**

→ **See Reference Only**

→ **Recall Independently**

→ **Retain Through Spaced Repetition**

The goal is not merely to mark a scripture as memorized, but to help the user retain it over time.

Mobile Design Requirements
==========================

The application should be designed mobile-first.

Requirements:

* Large touch targets.
* Minimal typing whenever possible.
* Scripture text should be easily readable.
* Avoid clutter.
* One primary task per screen.
* Support portrait orientation well.
* Work on standard desktop browsers.
* Buttons should be reachable comfortably when holding a phone.
* Practice should usually require only taps, swipes, or short text entry.
* Preserve readable scripture formatting and punctuation.

Daily Review
===========

The Daily Review screen should be the primary recurring-use screen.

It should display scriptures whose review date is due.

Suggested display:
Today's Review
--------------

**5 Scriptures Due**

For each scripture:

* Reference
* Current mastery level
* Days since last review
* Review button

After attempting the scripture, the user should be able to rate the result.

Suggested responses:

* Forgot It
* Difficult
* Got It
* Easy

These responses should influence the next review date.**

My Scriptures Screen
====================

Suggested filters:

* All
* Learning
* Review Due
* Memorized
* Available

Possible scripture card:

### Proverbs 3:5–6

**Learning Level:** 5  
**Text:** 82%  
**Reference:** 70%  
**Next Review:** Today

[Practice]

Cards should be large enough for comfortable touch interaction.

Home Screen
===========

The Home screen should be simple and optimized for quick mobile use.

Suggested content:
Scripture Learning
------------------

### Today

**5 Reviews Due**

[Start Daily Review]

### Currently Learning

**8 Scriptures**

[Continue Learning]

### Progress

**24 Memorized**

### Other Actions

[My Scriptures]

[Add Scripture]

[Browse Scriptures]

The most important action should be visually prominent:

**Start Daily Review**

Spaced Repetition
=================

The app should use spaced repetition to help retain memorized scriptures.

The exact algorithm may be selected by the development system.

A simple initial model might use intervals such as:

* Same day or later that day
* 1 day
* 3 days
* 7 days
* 14 days
* 30 days
* 60 days
* 90 days
* Longer-term maintenance reviews

A successful review should generally increase the interval.

A difficult or failed review should shorten the interval.

Example:

**Forgot It**

* Return scripture to a short review interval.
* Potentially reduce learning level.

**Difficult**

* Schedule relatively soon.

**Got It**

* Continue normal interval progression.

**Easy**

* Increase interval more aggressively.

The system should calculate and store the next review date.

Users
-----

* Scriptures and memorization progress are associated with a user.
* Each user should have a numeric user ID.
* The app is expected to support a relatively small user population, probably fewer than 30 users.
* Each user's learning progress, review schedule, and memorization status must remain separate from other users.
