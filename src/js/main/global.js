export const getById = (id) => {
  return document.getElementById(id);
};

export const setHtml = (property, string) => {
  document.documentElement.setAttribute(property, string);
};
