export const getById = (k) => {
  return document.getElementById(k);
};

export const create = (k) => {
  return document.createElement(k);
};

export const setHtml = (property, string) => {
  document.documentElement.setAttribute(property, string);
};
