module.exports = function ownsResource(user, mail) {
  return Boolean(user && mail && mail.userId === user.userId);
};
