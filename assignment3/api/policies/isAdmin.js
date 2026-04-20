module.exports = function isAdmin(user) {
  return user?.role === "admin";
};
