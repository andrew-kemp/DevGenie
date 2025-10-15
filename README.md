# DevGenie

**DevGenie** is a fun, modern portal for automated Dev account provisioning.

## Features

- User-friendly web form for Dev account requests
- Admin setup on first run
- Secure login for admins (future)
- Modern, responsive design

## Setup

1. Run the installer:

   ```bash
   bash installer.sh
   ```

2. Activate your Python virtual environment and install requirements:

   ```
   python3 -m venv venv
   source venv/bin/activate
   pip install -r requirements.txt
   ```

3. Run the app:

   ```
   flask run
   ```

## Structure

- `/app`: Flask app files
- `/scripts`: Helper SQL/schema scripts
- `installer.sh`: Initial setup script

---

**DevGenie** — Granting wishes for Dev accounts, one request at a time! 🧞

# Setting Up Microsoft Entra (Azure AD) SSO (SAML) for DevGenie

This guide explains how to configure Microsoft Entra (Azure AD) Single Sign-On (SSO) with SAML for DevGenie.

---

## 1. Create the Enterprise Application

1. Go to [Azure Portal](https://portal.azure.com)
2. Navigate to **Azure Active Directory** > **Enterprise applications** > **+ New application**
3. Choose **Create your own application**
4. Name it (e.g., `DevGenie-SAML`)
5. Select **Integrate any other application you don’t find in the gallery (Non-gallery)**, then **Create**

---

## 2. Configure SAML SSO in Azure

1. In your new app, go to **Single sign-on** > **SAML**
2. In the **Basic SAML Configuration** section, enter:

    | Field                                 | Value                                                      |
    |---------------------------------------|------------------------------------------------------------|
    | Identifier (Entity ID)                | `https://devgenie.andykemp.cloud/saml/metadata.php`        |
    | Reply URL (Assertion Consumer Service)| `https://devgenie.andykemp.cloud/saml/acs.php`             |
    | Logout URL (optional)                 | `https://devgenie.andykemp.cloud/saml/sls.php`             |

---

## 3. Copy IdP Values from Azure

In the **Set up [App Name]** and **SAML Signing Certificate** sections, find and save:

- **Azure AD Identifier** (IdP Entity ID):  
  Example: `https://sts.windows.net/<tenant-id>/`
- **Login URL** (Single Sign-On URL):  
  Example: `https://login.microsoftonline.com/<tenant-id>/saml2`
- **Logout URL** (Single Logout URL, optional):  
  Example: `https://login.microsoftonline.com/<tenant-id>/saml2/logout`
- **Certificate (Base64)**:  
  Download or copy the certificate (paste the full block, including `-----BEGIN CERTIFICATE-----` and `-----END CERTIFICATE-----`)

---

## 4. Enter IdP Settings in DevGenie

Paste the IdP values into your DevGenie SAML settings page or database:

| DevGenie SAML Setting   | Value from Azure Portal                       |
|------------------------ |-----------------------------------------------|
| IdP Entity ID           | Azure AD Identifier (e.g., `https://sts.windows.net/<tenant-id>/`) |
| Single Sign-On URL      | Login URL                                     |
| Single Logout URL       | Logout URL (optional)                         |
| IdP x509 Certificate    | Certificate (Base64), full block              |

> **Note:** If you get a SAML error about "Invalid issuer," use the value shown in your SAML response as the IdP Entity ID (typically the `sts.windows.net` URL).

---

## 5. Assign Users and Test

- In Azure, assign users/groups to the Enterprise Application.
- Test SSO by logging into DevGenie using "Login via Entra" or similar.

---

## Troubleshooting

- **Invalid issuer:**  
  Update IdP Entity ID in DevGenie to match the `Issuer` in the SAML Assertion (often `https://sts.windows.net/<tenant-id>/`).
- **Certificate errors:**  
  Ensure you are pasting the full Base64 certificate, including the `BEGIN` and `END` lines.

---

## References

- [Microsoft Docs: Configure SAML SSO](https://learn.microsoft.com/en-us/azure/active-directory/manage-apps/configure-single-sign-on-non-gallery-applications)
- [DevGenie Documentation](./README.md)
