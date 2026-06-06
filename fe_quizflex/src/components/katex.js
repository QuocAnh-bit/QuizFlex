import katex from "katex";
import "katex/dist/katex.min.css";

const renderLatex = (latex) => {
  if (!latex) return "";

  try {
    return katex.renderToString(latex, {
      throwOnError: false,
      displayMode: false,
    });
  } catch {
    return latex;
  }
};
