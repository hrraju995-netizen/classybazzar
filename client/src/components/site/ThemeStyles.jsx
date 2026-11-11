const ThemeStyles = ({ theme = {} }) => {
  const {
    primaryColor = '#eb6e1b',
    secondaryColor = '#111827',
    buttonColor = primaryColor,
    buttonHoverColor = '#111827',
    buttonTextColor = '#ffffff',
    badgeColor = '#ef4444',
    headerBackground = '#ffffff',
    headerTextColor = '#111827',
    menubarBackground = headerBackground,
    menubarTextColor = headerTextColor,
    topbarBackground = '#111111',
    topbarTextColor = '#fafafa',
    footerBackground = '#050505',
    footerTextColor = '#ffffff',
    bodyBackground = '#ffffff',
    cardBackground = '#ffffff',
    textColor = '#111827',
    fontHeading = 'Poppins',
    fontBody = 'Inter',
  } = theme;

  const css = `
    :root {
      --color-primary: ${primaryColor};
      --color-secondary: ${secondaryColor};
      --color-button: ${buttonColor};
      --color-button-hover: ${buttonHoverColor};
      --color-button-text: ${buttonTextColor};
      --color-badge: ${badgeColor};
      --color-header-bg: ${headerBackground};
      --color-header-text: ${headerTextColor};
      --color-menubar-bg: ${menubarBackground};
      --color-menubar-text: ${menubarTextColor};
      --color-topbar-bg: ${topbarBackground};
      --color-topbar-text: ${topbarTextColor};
      --color-footer-bg: ${footerBackground};
      --color-footer-text: ${footerTextColor};
      --color-body-bg: ${bodyBackground};
      --color-card-bg: ${cardBackground};
      --color-text: ${textColor};
      --font-heading: '${fontHeading}', var(--font-poppins), sans-serif;
      --font-body: '${fontBody}', var(--font-inter), sans-serif;
    }
    body {
      background-color: var(--color-body-bg);
      color: var(--color-text);
      font-family: var(--font-body);
    }
    h1, h2, h3, h4, h5, h6 {
      font-family: var(--font-heading);
    }
  `;

  return <style dangerouslySetInnerHTML={{ __html: css }} />;
};

export default ThemeStyles;
