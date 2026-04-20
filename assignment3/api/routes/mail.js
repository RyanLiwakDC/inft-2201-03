const express = require("express");
const mailData = require("../data/mail");
const authenticateJWT = require("../middleware/authenticateJWT");
const authorize = require("../middleware/authorize");
const rateLimit = require("../middleware/rateLimit");
const canViewMail = require("../policies/canViewMail");

const router = express.Router();

function loadMail(req, res, next) {
  const id = parseInt(req.params.id, 10);
  const mail = mailData.find((item) => item.id === id);

  if (!mail) {
    const err = new Error("Mail not found.");
    err.statusCode = 404;
    err.error = "NotFound";
    return next(err);
  }

  req.mail = mail;
  return next();
}

router.get(
  "/:id",
  authenticateJWT,
  loadMail,
  authorize(canViewMail),
  rateLimit,
  (req, res) => {
    res.json(req.mail);
  }
);

module.exports = router;
