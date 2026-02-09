/**
 * Cryptographic utilities for offline-first PWA
 * Uses Web Crypto API for PBKDF2 key derivation and AES-256-GCM encryption
 */

/**
 * Derive a 256-bit key from a password using PBKDF2-SHA256
 * @param password - User password
 * @param salt - Random salt (16 bytes)
 * @returns Derived CryptoKey for AES encryption
 */
export async function deriveKey(
    password: string,
    salt: Uint8Array,
): Promise<CryptoKey> {
    const encoder = new TextEncoder();
    const passwordBuffer = encoder.encode(password);

    // Import password as raw key
    const baseKey = await crypto.subtle.importKey(
        'raw',
        passwordBuffer,
        { name: 'PBKDF2' },
        false,
        ['deriveBits', 'deriveKey'],
    );

    // Derive 256-bit key using PBKDF2
    return crypto.subtle.deriveKey(
        {
            name: 'PBKDF2',
            salt: salt,
            iterations: 100000,
            hash: 'SHA-256',
        },
        baseKey,
        { name: 'AES-GCM', length: 256 },
        false,
        ['encrypt', 'decrypt'],
    );
}

/**
 * Encrypt plaintext using AES-256-GCM
 * @param plaintext - Data to encrypt
 * @param key - CryptoKey from deriveKey()
 * @param iv - Initialization vector (12 bytes)
 * @returns Ciphertext bytes
 */
export async function encrypt(
    plaintext: string,
    key: CryptoKey,
    iv: Uint8Array,
): Promise<Uint8Array> {
    const encoder = new TextEncoder();
    const data = encoder.encode(plaintext);

    const ciphertext = await crypto.subtle.encrypt(
        {
            name: 'AES-GCM',
            iv: iv,
        },
        key,
        data,
    );

    return new Uint8Array(ciphertext);
}

/**
 * Decrypt ciphertext using AES-256-GCM
 * @param ciphertext - Encrypted bytes
 * @param key - CryptoKey from deriveKey()
 * @param iv - Initialization vector (must match encryption IV)
 * @returns Decrypted plaintext
 */
export async function decrypt(
    ciphertext: Uint8Array,
    key: CryptoKey,
    iv: Uint8Array,
): Promise<string> {
    const plaintext = await crypto.subtle.decrypt(
        {
            name: 'AES-GCM',
            iv: iv,
        },
        key,
        ciphertext,
    );

    const decoder = new TextDecoder();
    return decoder.decode(plaintext);
}

/**
 * Generate random bytes for salt or IV
 * @param length - Number of bytes to generate
 * @returns Random Uint8Array
 */
export function getRandomBytes(length: number): Uint8Array {
    return crypto.getRandomValues(new Uint8Array(length));
}

/**
 * Encode Uint8Array to base64 for storage
 * @param bytes - Bytes to encode
 * @returns Base64 string
 */
export function bytesToBase64(bytes: Uint8Array): string {
    let binary = '';
    for (let i = 0; i < bytes.byteLength; i++) {
        binary += String.fromCharCode(bytes[i]);
    }
    return btoa(binary);
}

/**
 * Decode base64 to Uint8Array
 * @param base64 - Base64 string
 * @returns Uint8Array
 */
export function base64ToBytes(base64: string): Uint8Array {
    const binary = atob(base64);
    const bytes = new Uint8Array(binary.length);
    for (let i = 0; i < binary.length; i++) {
        bytes[i] = binary.charCodeAt(i);
    }
    return bytes;
}

/**
 * Encrypted data storage format
 */
export interface EncryptedData {
    salt: string; // base64-encoded salt (16 bytes)
    iv: string; // base64-encoded IV (12 bytes)
    ciphertext: string; // base64-encoded ciphertext
}

/**
 * Encrypt a password with a random salt, store in standard format
 * @param password - Password to encrypt
 * @returns EncryptedData object with salt, iv, and ciphertext
 */
export async function encryptPassword(
    password: string,
): Promise<EncryptedData> {
    const salt = getRandomBytes(16);
    const iv = getRandomBytes(12);
    const key = await deriveKey(password, salt);
    const ciphertext = await encrypt(password, key, iv);

    return {
        salt: bytesToBase64(salt),
        iv: bytesToBase64(iv),
        ciphertext: bytesToBase64(ciphertext),
    };
}

/**
 * Decrypt a password from encrypted data format
 * @param password - Password used to encrypt (needed to re-derive key)
 * @param encrypted - EncryptedData object with salt, iv, and ciphertext
 * @returns Decrypted password string
 */
export async function decryptPassword(
    password: string,
    encrypted: EncryptedData,
): Promise<string> {
    const salt = base64ToBytes(encrypted.salt);
    const iv = base64ToBytes(encrypted.iv);
    const ciphertext = base64ToBytes(encrypted.ciphertext);
    const key = await deriveKey(password, salt);
    return decrypt(ciphertext, key, iv);
}

/**
 * Hash a password using SHA-256 for simple verification
 * (NOT for password storage - use encryptPassword instead)
 * Used for quick local validation without re-decryption
 * @param password - Password to hash
 * @returns Base64-encoded hash
 */
export async function hashPassword(password: string): Promise<string> {
    const encoder = new TextEncoder();
    const data = encoder.encode(password);
    const hashBuffer = await crypto.subtle.digest('SHA-256', data);
    return bytesToBase64(new Uint8Array(hashBuffer));
}
