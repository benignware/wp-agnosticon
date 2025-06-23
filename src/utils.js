import IconControl from "./IconControl.jsx";

class Agnosticon {
  #data = {};

  constructor() {
    if (Agnosticon.instance) {
      return Agnosticon.instance;
    }

    this.#data = globalThis.AgnosticonData || {};
    Agnosticon.instance = this;
  }

  get icons() {
    return this.#data.icons || {};
  }

  /**
   * Fetch icons based on a query.
   * @param {string|null} query - The search query for icons.
   * @returns {object|null} - Best matching icon or all icons if no query is provided.
   */
  getIcons(query = '') {
    console.log('*** query:', query);
    if (!query) return Object.values(this.icons);
    
    if (query in this.icons) {
      return [this.icons[query]];
    }

    const [,prefix = ''] = query.match(/^([a-z_-]+)\:/i)?.[0] || [];
    const rest = query.slice(prefix.length);
    const [name = '', variant = ''] = rest.split(/[:]]+/);

    console.log('*** prefix:', prefix);
    console.log('*** rest:', rest);
    console.log('*** name:', name);
    console.log('*** variant:', variant);

    const queryTokens = name.toLowerCase().split(/[:-_\s]+/); // Split query into tokens

    const scoredIcons = Object.values(this.icons).map((icon) => {
      const iconTokens = icon.name.toLowerCase().split(/[-_\s]+/);

      // Calculate relevance score
      let score = 0;

      queryTokens.forEach((qToken) => {
        const matchIndex = iconTokens.indexOf(qToken);
        if (matchIndex !== -1) {
          score += 1; // Exact token match
        } else {
          // Partial match score, add based on relevance
          score += iconTokens.some((iToken) =>
            iToken.startsWith(qToken) || iToken.includes(qToken)
          )
            ? 0.5
            : 0;
        }
      });

      return { icon, score };
    });

    // Sort by relevance (higher score first) and return the first match
    const bestMatches = scoredIcons
      .sort((a, b) => b.score - a.score)
      .filter((match) => match.score > 0)
      .map((match) => match.icon);

    return bestMatches[0];
  }

  /**
   * Get metadata for a specific icon based on query.
   * @param {string} query - The search query for an icon.
   * @returns {object} - The metadata of the matching icon.
   */
  getIconMeta(query) {
    const icon = this.getIcons(query);
    return icon || {};
  }

  search(query = '') {
    return this.getIcons(query);
  }

  find(query = '') {
    const result = this.getIcons(query)[0];

    console.log('find result:', result);

    return result;
  }
}

// Ensure a single global instance
const instance = new Agnosticon();
instance.components = {
  IconControl
}
// Object.freeze(instance);

globalThis.agnosticon = instance;
